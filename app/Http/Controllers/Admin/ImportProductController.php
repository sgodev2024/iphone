<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Import;
use App\Models\Product;
use App\Services\CategoryService;
use App\Services\CompanyService;
use App\Services\ImportProductService;
use App\Services\ProductService;
use App\Services\StorageService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ImportProductController extends Controller
{
    protected $productService;

    protected $categoryService;

    protected $importProductService;

    protected $companyService;

    protected $storageService;

    public function __construct(ProductService $productService, CategoryService $categoryService, ImportProductService $importProductService, CompanyService $companyService, StorageService $storageService)
    {
        $this->productService = $productService;
        $this->categoryService = $categoryService;
        $this->importProductService = $importProductService;
        $this->companyService = $companyService;
        $this->storageService = $storageService;
    }

    public function index(Request $request)
    {
        $title = 'Nhập hàng';
        $search = $request->input('search');
        $import = $this->importProductService->getImportCoupon(10, $search);

        return view('admin.Importproduct.index', compact('title', 'import', 'search'));
    }

    public function bulkDelete(Request $request)
    {
        if (! Auth::check() || ! in_array((int) Auth::user()->role_id, [1, 2, 4], true)) {
            return response()->json([
                'message' => 'Bạn không có quyền xóa phiếu nhập.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:import_coupon,id'],
        ]);

        $ids = collect($validated['ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        try {
            $deletedCount = $this->importProductService->deleteImportCoupons($ids, Auth::user());

            return response()->json([
                'message' => "Đã xóa thành công {$deletedCount} phiếu nhập.",
            ]);
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $exception) {
            Log::error('Failed to bulk delete import coupons.', [
                'ids' => $ids,
                'user_id' => Auth::id(),
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau!',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function importdetail($id)
    {
        $title = 'Thông tin hóa đơn';
        $importdetail = $this->importProductService->getImportCouponByid($id);

        return view('admin.Importproduct.detail', compact('title', 'importdetail'));
    }

    public function add(Request $request)
    {
        $title = 'Nhập hàng';
        $ownerIds = $this->inventoryOwnerIds();
        $products = Product::query()
            ->whereIn('user_id', $ownerIds)
            ->latest()
            ->get();
        $category = $this->categoryService->getCategoryAllStaff();
        $supplier = $this->companyService->getCompany();
        $storage = $this->storageService->getAllStorage();
        $user = Auth::user();

        $preselectedProduct = Product::query()
            ->whereIn('user_id', $ownerIds)
            ->find($request->integer('product_id'));

        if ($preselectedProduct) {
            Import::query()->firstOrCreate(
                ['product_id' => $preselectedProduct->id],
                [
                    'quantity' => 1,
                    'price' => $preselectedProduct->price,
                    'total' => $preselectedProduct->price,
                ]
            );
        }

        return view('admin.Importproduct.add', compact('products', 'user', 'supplier', 'category', 'storage', 'title'));
    }

    public function importadd(Request $request)
    {
        $validated = $request->validate([
            'product' => ['required', 'integer', 'exists:products,id'],
        ]);
        $productId = (int) $validated['product'];
        $product = Product::query()
            ->whereIn('user_id', $this->inventoryOwnerIds())
            ->find($productId);

        if (! $product) {
            return response()->json([
                'error' => 'Sản phẩm không hợp lệ hoặc bạn không có quyền nhập sản phẩm này.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $products = Import::where('product_id', $productId)->first();
        if (! $products) {
            Import::create([
                'product_id' => $productId,
                'quantity' => 1,
                'price' => $product->price,
                'total' => $product->price,
            ]);
        }
        $import = Import::get();
        $sum = 0;
        foreach ($import as $item) {
            $sum += $item->total;
        }

        return response()->json([
            'import' => $import,
            'total' => $sum,
        ]);
    }

    public function importupdate(Request $request)
    {
        $validated = $request->validate([
            'dataId' => ['required', 'integer', 'exists:import,id'],
            'value' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);
        $import = Import::findOrFail($validated['dataId']);
        $import->update([
            'quantity' => $validated['value'],
            'total' => $import->price * $validated['value'],
        ]);
        $imports = Import::get();
        $sum = 0;
        foreach ($imports as $item) {
            $sum += $item->total;
        }

        return response()->json([
            'import' => $imports,
            'total' => $sum,
        ]);
    }

    public function importupdateprice(Request $request)
    {
        $validated = $request->validate([
            'dataId' => ['required', 'integer', 'exists:import,id'],
            'value' => ['required', 'numeric', 'min:0'],
        ]);
        $import = Import::findOrFail($validated['dataId']);
        $import->update([
            'price' => $validated['value'],
            'total' => $import->quantity * $validated['value'],
        ]);
        $imports = Import::get();
        $sum = 0;
        foreach ($imports as $item) {
            $sum += $item->total;
        }

        return response()->json([
            'import' => $imports,
            'total' => $sum,
        ]);
    }

    public function importdelete(Request $request)
    {
        $id = $request->id;
        $import = Import::find($id);
        $import->delete();
        $imports = Import::get();
        $sum = 0;
        foreach ($imports as $item) {
            $sum += $item->total;
        }

        return response()->json([
            'import' => $imports,
            'total' => $sum,
        ]);
    }

    public function listImport()
    {
        $import = Import::get();
        $category = $this->categoryService->getCategoryAllStaff();
        $sum = 0;
        foreach ($import as $item) {
            $sum += $item->total;
        }

        return response()->json([
            'import' => $import,
            'category' => $category,
            'total' => $sum,
        ]);
    }

    public function addCategory(Request $request)
    {
        $list_id = $request->selectedValues;
        $imports = Import::get();
        foreach ($list_id as $item) {
            $product = $this->productService->getProductByCategory($item);
            foreach ($product as $key => $value) {

                if (! $imports->contains('product_id', $value->id)) {
                    Import::create([
                        'product_id' => $value->id,
                        'quantity' => 1,
                        'price' => $value->price,
                        'total' => $value->price,
                    ]);
                }
            }
        }
        $import = Import::get();
        $sum = 0;
        foreach ($import as $item) {
            $sum += $item->total;
        }

        return response()->json([
            'import' => $import,
            'total' => $sum,
        ]);
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
}
