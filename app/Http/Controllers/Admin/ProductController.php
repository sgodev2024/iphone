<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductRequest;
use App\Models\Brand;
use App\Models\Categories;
use App\Models\ImportDetail;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\ProductStorage;
use App\Services\SaleStorageResolver;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly BranchContext $branchContext,
        private readonly SaleStorageResolver $saleStorageResolver
    ) {}

    public function index(Request $request)
    {
        $title = 'Sản phẩm';
        if ($request->ajax()) {
            $searchText = $request->input('s');
            $user = $request->user();
            $stockQuery = ProductStorage::query()
                ->selectRaw('COALESCE(SUM(quantity), 0)')
                ->whereColumn('product_storage.product_id', 'products.id');
            $this->branchContext->scopeThroughStorage($stockQuery, $user);

            $latestImportQuery = ImportDetail::query()
                ->select('import_detail.import_id')
                ->join('import_coupon', 'import_coupon.id', '=', 'import_detail.import_id')
                ->whereColumn('import_detail.product_id', 'products.id')
                ->orderByDesc('import_coupon.created_at')
                ->orderByDesc('import_coupon.id')
                ->limit(1);
            $this->branchContext->scopeThroughStorage($latestImportQuery, $user, 'import.storage');

            $products = Product::query()
                ->select('products.*')
                ->selectSub($stockQuery, 'storage_stock_quantity')
                ->selectSub($latestImportQuery, 'latest_import_coupon_id')
                ->withCount([
                    'imeis as imei_stock_count' => function ($query) use ($user) {
                        $query->where('status', ProductImei::STATUS_IN_STOCK);
                        $this->branchContext->scopeThroughStorage($query, $user);
                    },
                ])
                ->when(! empty($searchText), function ($query) use ($searchText) {
                    $query->where('name', 'like', "%$searchText%");
                });
            $this->branchContext->scopeThroughStorage(
                $products,
                $user,
                'productStorages.storage'
            );
            $products = $products
                ->latest()
                ->paginate(10)
                ->appends($request->query());

            $html = view('admin.product.table', compact('products'))->render();

            return successResponse(data: ['html' => $html], isToastr: false);
        }

        return view('admin.product.index', compact('title'));
    }

    public function create()
    {
        $title = 'Thêm sản phẩm';
        $categories = Categories::query()->latest()->pluck('name', 'id')->toArray();
        $brands = Brand::query()->latest()->pluck('name', 'id')->toArray();
        $product = null;
        $canChangeInventoryTracking = true;
        $inventoryTrackingLockedMessage = null;

        return view('admin.product.form', compact(
            'title',
            'categories',
            'brands',
            'product',
            'canChangeInventoryTracking',
            'inventoryTrackingLockedMessage'
        ));
    }

    public function store(ProductRequest $request)
    {
        return transaction(function () use ($request) {

            $data = $request->validated();

            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = uploadImages('thumbnail', 'products');
            } else {
                unset($data['thumbnail']);
            }

            $data['user_id'] = Auth::id();
            $data['code'] = generateCode('products', 'SP');
            $data['quantity'] = 0;

            Product::create($data);

            return successResponse('Thêm mới sản phẩm thành công.', code: Response::HTTP_CREATED);
        }, function (\Throwable $e) {
            Log::error('Failed to store product.', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        });
    }

    public function edit(string $id)
    {
        $productQuery = Product::query()->with(['category', 'brand']);
        if (! $this->branchContext->isGlobal(Auth::user())) {
            $productQuery->where('user_id', Auth::id());
        }
        $product = $productQuery->findOrFail($id);
        $title = "Cập nhật sản phẩm - {$product->name}";
        $categories = Categories::query()->latest()->pluck('name', 'id')->toArray();
        $brands = Brand::query()->latest()->pluck('name', 'id')->toArray();
        $canChangeInventoryTracking = $product->canChangeInventoryTracking();
        $inventoryTrackingLockedMessage = $canChangeInventoryTracking
            ? null
            : 'Không thể thay đổi phương thức quản lý tồn kho vì sản phẩm đã phát sinh dữ liệu kho hoặc giao dịch.';

        return view('admin.product.form', compact(
            'title',
            'categories',
            'brands',
            'product',
            'canChangeInventoryTracking',
            'inventoryTrackingLockedMessage'
        ));
    }

    public function update(ProductRequest $request, $id)
    {
        return transaction(function () use ($request, $id) {
            $productQuery = Product::query();
            if (! $this->branchContext->isGlobal($request->user())) {
                $productQuery->where('user_id', Auth::id());
            }
            $product = $productQuery->findOrFail($id);

            $oldThumbnail = $product->thumbnail;

            $data = $request->validated();

            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = uploadImages('thumbnail', 'products');
            } else {
                unset($data['thumbnail']);
            }

            $data['is_featured'] ??= 0;

            $updated = $product->update($data);

            if ($updated && $request->hasFile('thumbnail')) {
                deleteImage($oldThumbnail);
            }

            return successResponse('Cập nhật sản phẩm thành công.');
        }, function (\Throwable $e) use ($id) {
            Log::error('Failed to update product.', [
                'product_id' => (int) $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        });
    }
    public function searchForSale(Request $request)
    {
        /*
     * Hỗ trợ cả hai tên tham số:
     * - searchText: theo JavaScript hiện tại
     * - search: nếu đã sửa theo hướng dẫn trước
     */
        $searchText = trim((string) (
            $request->query('search')
            ?? $request->query('searchText')
            ?? ''
        ));

        $user = Auth::user();
        $storageId = $this->saleStorageResolver->resolveSaleStorageId(
            $user,
            $request->input('storage_id')
        );

        $products = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.code',
                'products.barcode',
                'products.thumbnail',
                'products.price_buy',
                'products.inventory_tracking',
                'products.user_id',
            ])

            /*
         * Tính số lượng tồn trong kho.
         * Nếu nhân viên có storage_id thì chỉ tính kho được gán.
         * Nếu admin/manager không có storage_id thì tính tổng các kho.
         */
            ->selectSub(
                ProductStorage::query()
                    ->selectRaw('COALESCE(SUM(product_storage.quantity), 0)')
                    ->whereColumn(
                        'product_storage.product_id',
                        'products.id'
                    )
                    ->when($storageId, function ($query) use ($storageId) {
                        $query->where(
                            'product_storage.storage_id',
                            $storageId
                        );
                    }),
                'available_quantity'
            )

            /*
         * Phạm vi sản phẩm:
         * - Nhân viên có kho: lấy sản phẩm thuộc kho đó.
         * - Admin/manager: lấy sản phẩm do tài khoản đó quản lý.
         */
            ->whereExists(function ($subQuery) use ($storageId) {
                $subQuery
                    ->selectRaw('1')
                    ->from('product_storage')
                    ->whereColumn(
                        'product_storage.product_id',
                        'products.id'
                    )
                    ->where(
                        'product_storage.storage_id',
                        $storageId
                    )
                    ->where(
                        'product_storage.quantity',
                        '>',
                        0
                    );
            })

            /*
         * Hiện tại ô tìm kiếm chỉ nên chọn sản phẩm quản lý
         * theo số lượng. Sản phẩm IMEI cần quét barcode để xác
         * định chính xác thiết bị được bán.
         */
            ->where(function ($query) {
                $query
                    ->whereNull('products.inventory_tracking')
                    ->orWhere(
                        'products.inventory_tracking',
                        'quantity'
                    );
            })

            /*
         * Tìm theo tên, mã sản phẩm hoặc barcode sản phẩm thường.
         */
            ->when($searchText !== '', function ($query) use ($searchText) {
                $query->where(function ($subQuery) use ($searchText) {
                    $subQuery
                        ->where(
                            'products.name',
                            'like',
                            '%' . $searchText . '%'
                        )
                        ->orWhere(
                            'products.code',
                            'like',
                            '%' . $searchText . '%'
                        )
                        ->orWhere(
                            'products.barcode',
                            'like',
                            '%' . $searchText . '%'
                        );
                });
            })

            ->orderBy('products.name')
            ->limit(30)
            ->get()

            /*
         * Chuẩn hóa dữ liệu đúng với JavaScript màn bán hàng.
         */
            ->map(function (Product $product) {
                $availableQuantity = (int) $product->available_quantity;

                return [
                    'id' => (int) $product->id,
                    'product_id' => (int) $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'barcode' => $product->barcode,
                    'thumbnail' => $product->thumbnail,
                    'thumbnail_url' => $product->thumbnail_url,
                    'price_buy' => (float) $product->price_buy,
                    'quantity' => $availableQuantity,
                    'available_quantity' => $availableQuantity,
                    'tracking_type' => 'quantity',
                ];
            })

            /*
         * Không trả về sản phẩm đã hết hàng.
         */
            ->filter(function (array $product) {
                return $product['available_quantity'] > 0;
            })
            ->values();

        /*
     * Trả trực tiếp mảng JSON vì JavaScript hiện tại gọi:
     * renderProductResults(res)
     */
        return response()->json($products);
    }

    public function import(Request $request) {}

    public function export()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $user = Auth::user();
        $stockQuery = ProductStorage::query()
            ->selectRaw('COALESCE(SUM(quantity), 0)')
            ->whereColumn('product_storage.product_id', 'products.id');
        $this->branchContext->scopeThroughStorage($stockQuery, $user);

        $productsQuery = Product::query()
            ->select('products.*')
            ->selectSub($stockQuery, 'visible_stock_quantity');
        $this->branchContext->scopeThroughStorage($productsQuery, $user, 'productStorages.storage');
        $products = $productsQuery->with(['category', 'brand'])->get();
        // Đặt tiêu đề cột
        $sheet->setCellValue('A1', 'Mã sản phẩm');
        $sheet->setCellValue('B1', 'tên sản phẩm');
        $sheet->setCellValue('C1', 'Số lương');
        $sheet->setCellValue('D1', 'Giá nhập');
        $sheet->setCellValue('E1', 'Giá bán');
        $sheet->setCellValue('F1', 'Danh mục');
        $sheet->setCellValue('G1', 'Thương hiệu');
        $sheet->setCellValue('H1', 'Đơn vị');

        // Lấy danh sách sản phẩm

        // Điền dữ liệu vào sheet
        $row = 2;
        foreach ($products as $product) {
            $sheet->setCellValue('A' . $row, $product->code);
            $sheet->setCellValue('B' . $row, $product->name);
            $sheet->setCellValue('C' . $row, $product->visible_stock_quantity);
            $sheet->setCellValue('D' . $row, $product->price);
            $sheet->setCellValue('E' . $row, $product->price_buy);
            $sheet->setCellValue('F' . $row, $product->category?->name);
            $sheet->setCellValue('G' . $row, $product->brand?->name);
            $sheet->setCellValue('H' . $row, $product->product_unit);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(20);

        // Tạo file Excel và lưu vào output stream
        $writer = new Xlsx($spreadsheet);

        // Đặt tên file
        $fileName = 'products.xlsx';

        // Trả về file dưới dạng download response
        $response = response()->stream(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]
        );

        return $response;
    }
}
