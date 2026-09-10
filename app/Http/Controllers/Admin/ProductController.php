<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductRequest;
use App\Models\Brand;
use App\Models\Branch;
use App\Models\Categories;
use App\Models\ImportDetail;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\ProductStorage;
use App\Models\Storage;
use App\Services\SaleStorageResolver;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
        $user = $request->user();
        $hasBranchCatalog = Schema::hasColumn('products', 'branch_id') && Schema::hasTable('branches');
        $branches = $hasBranchCatalog && $user->isAdministrator()
            ? Branch::query()->orderBy('name')->get(['id', 'name'])
            : collect();
        $branchId = $hasBranchCatalog && $user->isAdministrator() && $request->filled('branch_id')
            ? (int) $request->input('branch_id')
            : ($hasBranchCatalog && $user->branch_id ? (int) $user->branch_id : null);

        if ($user->isAdministrator() && $branchId !== null) {
            abort_unless(Branch::query()->whereKey($branchId)->exists(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $title = 'Sản phẩm';
        if ($request->ajax()) {
            $searchText = $request->input('s');
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
            if ($hasBranchCatalog) {
                $products->with('branch:id,name');
            }
            $this->branchContext->scope($products, $user, 'products.branch_id');
            if ($user->isAdministrator() && $branchId !== null) {
                $products->where('products.branch_id', $branchId);
            }
            $products = $products
                ->latest()
                ->paginate(10)
                ->appends($request->query());

            $html = view('admin.product.table', compact('products', 'user', 'hasBranchCatalog'))->render();

            return successResponse(data: ['html' => $html], isToastr: false);
        }

        return view('admin.product.index', compact('title', 'branches', 'branchId', 'hasBranchCatalog'));
    }

    public function create(Request $request)
    {
        $title = 'Thêm sản phẩm';
        $user = $request->user();
        $hasBranchCatalog = Schema::hasColumn('products', 'branch_id') && Schema::hasColumn('categories', 'branch_id') && Schema::hasTable('branches');
        $branches = $hasBranchCatalog && $user->isAdministrator()
            ? Branch::query()->orderBy('name')->get(['id', 'name'])
            : collect();
        $branchId = $hasBranchCatalog && $user->isAdministrator()
            ? ($request->filled('branch_id') ? (int) $request->input('branch_id') : null)
            : ($hasBranchCatalog ? $this->branchContext->branchId($user) : null);
        if ($branchId !== null) {
            abort_unless(Branch::query()->whereKey($branchId)->exists(), Response::HTTP_NOT_FOUND);
        }
        $categories = Categories::query()
            ->when($hasBranchCatalog && $branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->when($hasBranchCatalog && $branchId === null, fn ($query) => $query->whereRaw('1 = 0'))
            ->latest()
            ->pluck('name', 'id')
            ->toArray();
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
            'inventoryTrackingLockedMessage',
            'branches',
            'branchId',
            'hasBranchCatalog'
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
            if (Schema::hasColumn('products', 'branch_id')) {
                $data['branch_id'] = $this->branchContext->resolveWriteBranch(
                    $request->user(),
                    $request->user()->isAdministrator() ? (int) $request->input('branch_id') : null
                );
            }
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
        $user = Auth::user();
        $hasBranchCatalog = Schema::hasColumn('products', 'branch_id') && Schema::hasColumn('categories', 'branch_id') && Schema::hasTable('branches');
        $productQuery = Product::query()->with(['category', 'brand']);
        if ($hasBranchCatalog) {
            $productQuery->with('branch');
        }
        $this->branchContext->scope($productQuery, $user, 'products.branch_id');
        if (! Schema::hasColumn('products', 'branch_id') && ! $user->isAdministrator()) {
            $productQuery->where('user_id', $user->id);
        }
        $product = $productQuery->findOrFail($id);
        $title = "Cập nhật sản phẩm - {$product->name}";
        $categories = Categories::query()
            ->when($hasBranchCatalog, fn ($query) => $query->where('branch_id', $product->branch_id))
            ->latest()->pluck('name', 'id')->toArray();
        $branches = $hasBranchCatalog && $user->isAdministrator()
            ? Branch::query()->whereKey($product->branch_id)->get(['id', 'name'])
            : collect();
        $branchId = $hasBranchCatalog ? (int) $product->branch_id : null;
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
            'inventoryTrackingLockedMessage',
            'branches',
            'branchId',
            'hasBranchCatalog'
        ));
    }

    public function update(ProductRequest $request, $id)
    {
        $productQuery = Product::query();
        $this->branchContext->scope($productQuery, $request->user(), 'products.branch_id');
        if (! Schema::hasColumn('products', 'branch_id') && ! $request->user()->isAdministrator()) {
            $productQuery->where('user_id', $request->user()->id);
        }
        $product = $productQuery->findOrFail($id);

        return transaction(function () use ($request, $product) {
            $oldThumbnail = $product->thumbnail;

            $data = $request->validated();
            unset($data['branch_id']);

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
        $storageBranchId = Schema::hasColumn('storages', 'branch_id')
            ? Storage::query()->whereKey($storageId)->value('branch_id')
            : null;

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
            ->when(Schema::hasColumn('products', 'branch_id'), function ($query) use ($storageBranchId) {
                $query->addSelect('products.branch_id')
                    ->where('products.branch_id', (int) $storageBranchId);
            })

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

    public function export(Request $request)
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
        $this->branchContext->scope($productsQuery, $user, 'products.branch_id');
        if ($user->isAdministrator() && $request->filled('branch_id')) {
            $branchId = (int) $request->input('branch_id');
            abort_unless(Branch::query()->whereKey($branchId)->exists(), Response::HTTP_UNPROCESSABLE_ENTITY);
            $productsQuery->where('products.branch_id', $branchId);
        }
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
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            ]
        );

        return $response;
    }
}
