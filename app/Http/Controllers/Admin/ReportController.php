<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportCoupon;
use App\Models\ImportDetail;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductStorage;
use App\Models\Storage;
use App\Services\ProductService;
use App\Services\ProductStorageService;
use App\Services\ProfitReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class ReportController extends Controller
{
    protected $productStorageService;
    protected $productService;

    public function __construct(ProductStorageService $productStorageService, ProductService $productService)
    {
        $this->productStorageService = $productStorageService;
        $this->productService = $productService;
    }

    public function index()
    {
        try {
            $title = 'Báo cáo xuất nhập tồn';
            $storages = $this->inventoryStorageQuery()
                ->orderBy('name', 'asc')
                ->get();
            $storage = $this->resolveInitialInventoryStorage($storages);
            $products = [];
            $latestImportDate = null;
            $yesterday = now()->subDay()->toDateString();
            $inventoryWarning = null;

            if ($storage) {
                $storage_id = $storage->id;
                $products = $this->productStorageService->inventoryReport($storage_id);

                // Lấy thêm thông tin kho và ngày tạo phiếu nhập
                $latestImportCoupon = ImportCoupon::where('storage_id', $storage_id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $latestImportDate = $latestImportCoupon ? $latestImportCoupon->created_at : null;
            } else {
                $inventoryWarning = 'Tài khoản hiện chưa có kho trong phạm vi quản lý. Vui lòng tạo kho hoặc gán kho trước khi xem báo cáo tồn kho.';

                Log::warning('Inventory report has no available storage.', [
                    'message' => $inventoryWarning,
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'user_id' => Auth::id(),
                    'owner_ids' => $this->inventoryOwnerIds(),
                    'assigned_storage_id' => Auth::user()?->storage_id,
                ]);
            }

            return view('admin.inventory.index', compact('title', 'products', 'storages', 'storage', 'latestImportDate', 'yesterday', 'inventoryWarning'));
        } catch (Throwable $e) {
            $this->logInventoryException($e, 'Failed to get Inventory Report');

            $title = 'Báo cáo xuất nhập tồn';
            $storages = collect();
            $storage = null;
            $products = [];
            $latestImportDate = null;
            $yesterday = now()->subDay()->toDateString();
            $inventoryWarning = 'Không thể tải dữ liệu báo cáo tồn kho. Vui lòng thử lại sau hoặc liên hệ quản trị viên.';

            return view('admin.inventory.index', compact('title', 'products', 'storages', 'storage', 'latestImportDate', 'yesterday', 'inventoryWarning'));
        }
    }

    public function getReportByStorage(Request $request)
    {
        try {
            $storage_id = (int) $request->input('storage_id');

            if ($storage_id <= 0) {
                return response()->json([
                    'message' => 'Vui lòng chọn kho cần xem báo cáo.',
                ], 422);
            }

            $storage = $this->inventoryStorageQuery()->find($storage_id);

            if (! $storage) {
                Log::warning('Inventory report requested storage outside user scope.', [
                    'message' => 'Không tìm thấy kho trong phạm vi quản lý của tài khoản hiện tại.',
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'user_id' => Auth::id(),
                    'storage_id' => $storage_id,
                    'owner_ids' => $this->inventoryOwnerIds(),
                    'assigned_storage_id' => Auth::user()?->storage_id,
                ]);

                return response()->json([
                    'message' => 'Không tìm thấy kho trong phạm vi quản lý của tài khoản hiện tại.',
                ], 404);
            }

            $products = $this->productStorageService->inventoryReport($storage_id);

            // Additional information
            $latestImportCoupon = ImportCoupon::where('storage_id', $storage_id)
                ->orderBy('created_at', 'desc')
                ->first();
            $latestImportDate = $latestImportCoupon ? $latestImportCoupon->created_at : null;
            $yesterday = now()->subDay()->toDateString();

            return response()->json([
                'products' => $products,
                'storage' => $storage,
                'latestImportDate' => $latestImportDate,
                'yesterday' => $yesterday
            ]);
        } catch (Throwable $e) {
            $this->logInventoryException($e, 'Failed to get Inventory Report by storage', [
                'storage_id' => $request->input('storage_id'),
            ]);

            return response()->json([
                'message' => 'Không thể tải báo cáo tồn kho. Vui lòng thử lại sau.',
            ], 500);
        }
    }

    private function inventoryStorageQuery()
    {
        return app(\App\Support\BranchContext::class)
            ->scopeStorages(Storage::query(), Auth::user());
    }

    private function resolveInitialInventoryStorage(Collection $storages): ?Storage
    {
        $assignedStorageId = Auth::user()?->storage_id;

        if ($assignedStorageId) {
            $assignedStorage = $storages->firstWhere('id', (int) $assignedStorageId);

            if ($assignedStorage) {
                return $assignedStorage;
            }
        }

        return $storages->first();
    }

    private function inventoryOwnerIds(): array
    {
        $user = Auth::user();

        return collect([$user?->id, $user?->manager_id])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function logInventoryException(Throwable $e, string $message, array $context = []): void
    {
        Log::error($message, array_merge([
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'user_id' => Auth::id(),
            'owner_ids' => $this->inventoryOwnerIds(),
            'assigned_storage_id' => Auth::user()?->storage_id,
        ], $context));
    }

    public function getProductsWithSmallQuanity(Request $request)
    {
        try {
            $storage_id = (int) $request->input('storage_id');
            $storage = $this->inventoryStorageQuery()->find($storage_id);

            if (! $storage) {
                return response()->json([
                    'message' => 'Storage not found in the current scope.',
                ], 404);
            }

            $latestImport = ImportCoupon::where('storage_id', $storage_id)
                ->orderByDesc('created_at')
                ->first();

            $productsInStorage = ProductStorage::where('storage_id', $storage_id)
                ->where('quantity', '<=', 5)
                ->with('product')
                ->get();

            $report = [];

            foreach ($productsInStorage as $productStorage) {
                $currentProductId = $productStorage->product_id;
                $currentQuantity = $productStorage->quantity;

                $importedQuantity = 0;
                $quantityBeforeImport = $currentQuantity;
                $beforeImportValue = $currentQuantity * $productStorage->product->price;
                $importedValue = 0;
                $soldQuantity = 0;
                $soldValue = 0;
                $currentValue = $currentQuantity * $productStorage->product->price;

                if ($latestImport) {
                    $latestImportDetail = ImportDetail::where('import_id', $latestImport->id)
                        ->where('product_id', $currentProductId)
                        ->first();

                    if ($latestImportDetail) {
                        $importedQuantity = $latestImportDetail->quantity;

                        $soldQuantity = OrderDetail::whereHas('order', function ($query) use ($latestImport) {
                            $query->where('created_at', '>', $latestImport->created_at);
                        })->where('product_id', $currentProductId)
                            ->where('storage_id', $storage_id)
                            ->sum('quantity');

                        $quantityBeforeImport = $currentQuantity + $soldQuantity - $importedQuantity;
                        $beforeImportValue = $quantityBeforeImport * $productStorage->product->price;
                        $importedValue = $importedQuantity * $latestImportDetail->price;
                        $soldValue = $soldQuantity * $productStorage->product->price_buy;
                        $currentValue = $currentQuantity * $productStorage->product->price;
                    }
                }

                $report[] = [
                    'product_id' => $currentProductId,
                    'current_quantity' => $currentQuantity,
                    'imported_quantity' => $importedQuantity,
                    'quantity_before_import' => $quantityBeforeImport,
                    'before_import_value' => $beforeImportValue,
                    'imported_value' => $importedValue,
                    'sold_quantity' => $soldQuantity,
                    'sold_value' => $soldValue,
                    'current_value' => $currentValue,
                    'product' => $productStorage->product,
                ];
            }
            return $report;
        } catch (Exception $e) {
            Log::error("Failed to fetch products with quantity fewer or equal than 5" . $e->getMessage());
            throw new Exception('Failed to fetch products with quantity fewer or equal than 5');
        }
    }

    public function profitIndex()
    {
        $title = 'Báo cáo lợi nhuận';
        $storages = $this->inventoryStorageQuery()->orderBy('name')->get();
        $initialStorage = $this->resolveInitialInventoryStorage($storages);

        return view('admin.profit.index', compact('title', 'storages', 'initialStorage'));
    }


    public function getProfitReport(Request $request)
    {
        $data = $this->validatedProfitInput($request, false);
        $service = app(ProfitReportService::class);
        $storage = isset($data['storage_id'])
            ? $service->storage(Auth::user(), (int) $data['storage_id'])
            : null;

        return response()->json([
            'product' => $service->report(
                Auth::user(), $storage, $data['filter'] ?? 'all',
                $data['startDate'] ?? null, $data['endDate'] ?? null, trim($data['search'] ?? '')
            ),
        ]);
    }


    public function getProfitReportByFilterNew(Request $request)
    {
        $data = $this->validatedProfitInput($request, true);
        $service = app(ProfitReportService::class);
        $storage = $service->storage(Auth::user(), (int) $data['storage_id']);

        return response()->json([
            'product' => $service->report(
                Auth::user(), $storage, $data['filter'],
                $data['startDate'] ?? null, $data['endDate'] ?? null, trim($data['search'] ?? '')
            ),
        ]);
    }

    public function getProfitReportByFilterPDF(Request $request)
    {
        $data = $this->validatedProfitInput($request, true);
        $service = app(ProfitReportService::class);
        $storage = $service->storage(Auth::user(), (int) $data['storage_id']);
        $rows = $service->report(
            Auth::user(), $storage, $data['filter'],
            $data['startDate'] ?? null, $data['endDate'] ?? null, trim($data['search'] ?? '')
        );
        $pdf = Pdf::loadView('admin.profit.myPDF', [
            'listprofit' => $rows,
            'startDate' => $data['startDate'] ?? null,
            'endDate' => $data['endDate'] ?? null,
            'storage' => $storage->name,
            'filter' => $data['filter'],
        ]);

        return $pdf->download('profit_report.pdf');
    }

    private function validatedProfitInput(Request $request, bool $requireStorage): array
    {
        return $request->validate([
            'storage_id' => [$requireStorage ? 'required' : 'nullable', 'integer', 'min:1'],
            'filter' => [$requireStorage ? 'required' : 'sometimes', Rule::in(['all', '1', '2', '3', '4', '5', '6'])],
            'startDate' => ['required_if:filter,6', 'prohibited_unless:filter,6', 'nullable', 'date_format:Y-m-d'],
            'endDate' => ['required_if:filter,6', 'prohibited_unless:filter,6', 'nullable', 'date_format:Y-m-d', 'after_or_equal:startDate'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
    }



}
