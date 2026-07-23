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
        $user = Auth::user();

        if (! $user) {
            return Storage::query()->whereRaw('1 = 0');
        }

        $ownerIds = $this->inventoryOwnerIds();

        return Storage::query()
            ->where(function ($query) use ($ownerIds, $user) {
                if (! empty($ownerIds)) {
                    $query->whereIn('user_id', $ownerIds);
                }

                if ($user->storage_id) {
                    $query->orWhere('id', (int) $user->storage_id);
                }
            });
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
            $storage_id = $request->input('storage_id');
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
            $storages = Storage::orderBy('name', 'asc')->get();
            $storage = Storage::first();
            $storage_id = $storage->id;
            $profits = $this->profitService->profitReport(1, $storage_id);
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

            $profits = $this->profitService->profitReport($filter, $storage_id, $start_date, $end_date);

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
            $listorderdetail = OrderDetail::with('product')->get();

            $listprofit = [];
            foreach ($listorderdetail as $orderDetail) {
                $productId = $orderDetail->product_id;

                if (!isset($listprofit[$productId])) {
                    $listprofit[$productId] = [
                        'product' => $orderDetail->product,
                        'quantity' => 0,
                    ];
                }
                $listprofit[$productId]['quantity'] += $orderDetail->quantity;
            }

            $listprofitArray = array_values($listprofit);
            return response()->json([
                'product' => $listprofitArray
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get Profit Report: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get Profit report'], 500);
        }
    }


    public function getProfitReportByFilterNew(Request $request)
    {
        log::info(1);
        try {
            $storage_id = $request->input('storage_id');
            $filter = $request->input('filter');
            $listorderdetail = [];
            switch ($filter) {
                case '1':
                    $today = Carbon::today();
                    $listorderdetail = OrderDetail::where('storage_id', $storage_id)->whereDate('created_at', $today)->with('product')->get();

                    break;
                case '2':
                    $startOfWeek = Carbon::now()->startOfWeek();
                    $endOfWeek = Carbon::now()->endOfWeek();
                    $listorderdetail = OrderDetail::where('storage_id', $storage_id)
                        ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                        ->with('product')
                        ->get();

                    break;
                case '3':
                    $startOfMonth = Carbon::now()->startOfMonth();
                    $endOfMonth = Carbon::now()->endOfMonth();
                    $listorderdetail = OrderDetail::where('storage_id', $storage_id)
                        ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                        ->with('product')
                        ->get();

                    break;
                case '4':
                    $startOfQuarter = Carbon::now()->startOfQuarter();
                    $endOfQuarter = Carbon::now()->endOfQuarter();

                    $listorderdetail = OrderDetail::where('storage_id', $storage_id)
                        ->whereBetween('created_at', [$startOfQuarter, $endOfQuarter])
                        ->with('product')
                        ->get();

                    break;

                case '5':
                    $startDate = Carbon::now()->startOfYear();
                    $endDate = Carbon::now()->endOfYear();
                    $listorderdetail = OrderDetail::where('storage_id', $storage_id)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->with('product')
                        ->get();

                    break;

                case '6':
                    $startDate = $request->startDate;
                    $endDate = $request->endDate;

                    $startDate = Carbon::parse($startDate)->startOfDay();
                    $endDate = Carbon::parse($endDate)->endOfDay();

                    $listorderdetail = OrderDetail::where('storage_id', $storage_id)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->with('product')
                        ->get();

                    break;

                default:
            }
            // $listorderdetail = OrderDetail::where('storage_id', $storage_id)->with('product')->get();
            $listprofit = [];
            foreach ($listorderdetail as $key => $orderDetail) {
                $productId = $orderDetail->product_id;

                if (!isset($listprofit[$productId])) {
                    $listprofit[$productId] = [
                        'product' => $orderDetail->product,
                        'quantity' => 0,
                    ];
                }
                $listprofit[$productId]['quantity'] += $orderDetail->quantity;
            }
            $listprofitArray = array_values($listprofit);
            return response()->json([

                'product' => $listprofitArray
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get Profit Report: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get Profit report'], 500);
        }
    }

    public function getProfitReportByFilterPDF(Request $request)
    {
        try {
            $storage_id = $request->input('storage_id');
            $filter = $request->input('filter');
            $listorderdetail = [];
            switch ($filter) {
                case '1':
                    $today = Carbon::today();
                    $listorderdetail = OrderDetail::where('storage_id', $storage_id)->whereDate('created_at', $today)->with('product')->get();

                    break;
                case '2':
                    $startOfWeek = Carbon::now()->startOfWeek();
                    $endOfWeek = Carbon::now()->endOfWeek();
                    $listorderdetail = OrderDetail::where('storage_id', $storage_id)
                        ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                        ->with('product')
                        ->get();

                    break;
                case '3':
                    $startOfMonth = Carbon::now()->startOfMonth();
                    $endOfMonth = Carbon::now()->endOfMonth();
                    $listorderdetail = OrderDetail::where('storage_id', $storage_id)
                        ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                        ->with('product')
                        ->get();

                    break;
                case '4':
                    $startOfQuarter = Carbon::now()->startOfQuarter();
                    $endOfQuarter = Carbon::now()->endOfQuarter();

                    $listorderdetail = OrderDetail::where('storage_id', $storage_id)
                        ->whereBetween('created_at', [$startOfQuarter, $endOfQuarter])
                        ->with('product')
                        ->get();

                    break;

                case '5':
                    $startDate = Carbon::now()->startOfYear();
                    $endDate = Carbon::now()->endOfYear();
                    $listorderdetail = OrderDetail::where('storage_id', $storage_id)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->with('product')
                        ->get();

                    break;

                case '6':
                    $startDate = $request->startDate;
                    $endDate = $request->endDate;

                    $startDate = Carbon::parse($startDate)->startOfDay();
                    $endDate = Carbon::parse($endDate)->endOfDay();

                    $listorderdetail = OrderDetail::where('storage_id', $storage_id)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->with('product')
                        ->get();

                    break;

                default:
            }
            // $listorderdetail = OrderDetail::where('storage_id', $storage_id)->with('product')->get();
            $listprofit = [];
            foreach ($listorderdetail as $key => $orderDetail) {
                $productId = $orderDetail->product_id;

                if (!isset($listprofit[$productId])) {
                    $listprofit[$productId] = [
                        'product' => $orderDetail->product,
                        'quantity' => 0,
                    ];
                }
                $listprofit[$productId]['quantity'] += $orderDetail->quantity;
            }
            $listprofitArray = array_values($listprofit);

            $storage = Storage::find($storage_id);
            if ($filter == 6) {
                $startDate = $request->startDate;
                $endDate = $request->endDate;

                $pdf = PDF::loadView('admin.profit.myPDF', [
                    'listprofit' => $listprofit,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'storage' => $storage->name,
                    'filter' => $filter
                ]);
            } else {
                $startDate = $request->startDate;
                $endDate = $request->endDate;

                $pdf = PDF::loadView('admin.profit.myPDF', [
                    'listprofit' => $listprofit,
                    'storage' => $storage->name,
                    'filter' => $filter,
                ]);
            }


            // Trả về file PDF
            return $pdf->download('profit_report.pdf');
        } catch (Exception $e) {
            Log::error('Failed to get Profit Report: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get Profit report'], 500);
        }
    }
}
