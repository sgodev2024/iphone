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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

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
            $storages = Storage::orderBy('name', 'asc')->get();
            $storage = Storage::first();
            $storage_id = $storage->id;
            $products = $this->productStorageService->inventoryReport($storage_id);

            // Lấy thêm thông tin kho và ngày tạo phiếu nhập
            $latestImportCoupon = ImportCoupon::where('storage_id', $storage_id)
                ->orderBy('created_at', 'desc')
                ->first();

            $latestImportDate = $latestImportCoupon ? $latestImportCoupon->created_at : null;

            $yesterday = now()->subDay()->toDateString();

            return view('admin.inventory.index', compact('title', 'products', 'storages', 'storage', 'latestImportDate', 'yesterday'));
        } catch (Exception $e) {
            Log::error('Failed to get Inventory Report: ' . $e->getMessage());
            return ApiResponse::error('Failed to get Inventory Report', 500);
        }
    }

    public function getReportByStorage(Request $request)
    {
        try {
            $storage_id = $request->storage_id;
            $products = $this->productStorageService->inventoryReport($storage_id);

            // Additional information
            $storage = Storage::find($storage_id);
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
        } catch (Exception $e) {
            Log::error('Failed to get Inventory Report: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get Inventory Report'], 500);
        }
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
            // dd($profits);
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
