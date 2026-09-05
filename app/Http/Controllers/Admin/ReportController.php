<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ImportCoupon;
use App\Models\ImportDetail;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductStorage;
use App\Models\Storage;
use App\Services\ProductService;
use App\Services\ProductStorageService;
use App\Services\ProfitService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Throwable;

class ReportController extends Controller
{
    protected $productStorageService;
    protected $productService;
    protected $profitService;
    public function __construct(ProductStorageService $productStorageService, ProductService $productService, ProfitService $profitService)
    {
        $this->productStorageService = $productStorageService;
        $this->productService = $productService;
        $this->profitService = $profitService;
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
        try {
            $title = 'Báo cáo lợi nhuận';
            $storages = $this->inventoryStorageQuery()->orderBy('name', 'asc')->get();
            $storage = $this->resolveInitialInventoryStorage($storages);
            $profits = $storage
                ? $this->profitService->profitReport(Auth::user(), 1, $storage->id)
                : [];
            return view('admin.profit.index', compact('title', 'profits', 'storages'));
        } catch (Exception $e) {
            Log::error('Failed to get Profit Report: ' . $e->getMessage());
            return ApiResponse::error('Failed to get Profit Report', 500);
        }
    }

    public function getProfitReportByFilter(Request $request)
    {
        try {
            $storage_id = $request->input('storage_id');
            $filter = $request->input('filter');
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');

            // Kiểm tra các giá trị đầu vào
            if ($filter == 6 && ($start_date === null || $end_date === null)) {
                return response()->json(['error' => 'Vui lòng chọn ngày bắt đầu và kết thúc'], 400);
            }

            $storage = $this->inventoryStorageQuery()->findOrFail((int) $storage_id);
            $profits = $this->profitService->profitReport(Auth::user(), $filter, $storage->id, $start_date, $end_date);

            return response()->json([
                'profits' => $profits,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get Profit Report: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get Profit report'], 500);
        }
    }

    public function getProfitReport()
    {
        try {
            return response()->json([
                'product' => $this->aggregateProfitByProduct($this->profitDetailsQuery()->get()),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get Profit Report: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get Profit report'], 500);
        }
    }


    public function getProfitReportByFilterNew(Request $request)
    {
        try {
            return response()->json([
                'product' => $this->aggregateProfitByProduct(
                    $this->filteredProfitDetails($request)
                ),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get Profit Report: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get Profit report'], 500);
        }
    }

    public function getProfitReportByFilterPDF(Request $request)
    {
        try {
            $storage = $this->inventoryStorageQuery()->findOrFail($request->input('storage_id'));
            $filter = (string) $request->input('filter');
            $pdf = PDF::loadView('admin.profit.myPDF', [
                'listprofit' => $this->aggregateProfitByProduct($this->filteredProfitDetails($request)),
                'startDate' => $request->startDate,
                'endDate' => $request->endDate,
                'storage' => $storage->name,
                'filter' => $filter,
            ]);

            return $pdf->download('profit_report.pdf');
        } catch (Exception $e) {
            Log::error('Failed to get Profit Report: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get Profit report'], 500);
        }
    }

    private function filteredProfitDetails(Request $request): Collection
    {
        $storage = $this->inventoryStorageQuery()->findOrFail((int) $request->input('storage_id'));
        $query = $this->profitDetailsQuery()
            ->where('storage_id', $storage->id)
            ->whereHas('order', fn ($orderQuery) => $orderQuery->where('branch_id', $storage->branch_id));

        match ((string) $request->input('filter')) {
            '1' => $query->whereDate('created_at', Carbon::today()),
            '2' => $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]),
            '3' => $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]),
            '4' => $query->whereBetween('created_at', [Carbon::now()->startOfQuarter(), Carbon::now()->endOfQuarter()]),
            '5' => $query->whereBetween('created_at', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()]),
            '6' => $query->whereBetween('created_at', [
                Carbon::parse($request->startDate)->startOfDay(),
                Carbon::parse($request->endDate)->endOfDay(),
            ]),
            default => null,
        };

        return $query->get();
    }

    private function profitDetailsQuery()
    {
        return OrderDetail::query()
            ->whereHas('order', function ($query): void {
                $query->where('status', 1);
                app(\App\Support\BranchContext::class)->scope($query, Auth::user());
            })
            ->with(['product', 'productImei.importDetail', 'order.orderDetails']);
    }

    private function aggregateProfitByProduct(Collection $details): array
    {
        return $details
            ->groupBy('product_id')
            ->map(function (Collection $productDetails): array {
                $product = $productDetails->first()->product;
                $quantity = 0;
                $revenue = 0.0;
                $cost = 0.0;

                foreach ($productDetails as $detail) {
                    $lineQuantity = (int) $detail->quantity;
                    $lineGross = (float) $detail->price * $lineQuantity;
                    $orderSubtotal = (float) $detail->order?->orderDetails->sum(
                        fn ($orderDetail) => (float) $orderDetail->price * (int) $orderDetail->quantity
                    );
                    $unitCost = $detail->productImei?->importDetail?->price
                        ?? $detail->product?->price_buy
                        ?? 0;

                    $quantity += $lineQuantity;
                    $revenue += $orderSubtotal > 0
                        ? (float) $detail->order->total_money * ($lineGross / $orderSubtotal)
                        : 0;
                    $cost += (float) $unitCost * $lineQuantity;
                }

                $profit = $revenue - $cost;

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'profit' => $profit,
                    'rate' => $revenue > 0 ? ($profit / $revenue) * 100 : 0,
                ];
            })
            ->values()
            ->all();
    }
}
