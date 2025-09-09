<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Import;
use App\Models\Supplier;
use App\Services\CategoryService;
use App\Services\CompanyService;
use App\Services\ImportProductService;
use App\Services\ProductService;
use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function importdetail($id)
    {
        $title = 'Thông tin hóa đơn';
        $importdetail = $this->importProductService->getImportCouponByid($id);
        return view('admin.Importproduct.detail', compact('title', 'importdetail'));
    }

    public function add()
    {
        $title = 'Nhập hàng';
        $products = $this->productService->getProductAll_Staff();
        $category = $this->categoryService->getCategoryAllStaff();
        $supplier = $this->companyService->getCompany();
        $storage = $this->storageService->getAllStorage();
        $user = Auth::user();
        return view('admin.Importproduct.add', compact('products', 'user', 'supplier', 'category', 'storage', 'title'));
    }

    public function importadd(Request $request)
    {
        $productId = $request->input('product');
        $product = $this->productService->getProductById($productId);
        $products = Import::where('product_id', $productId)->first();
        if (!$products) {
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
            'total' => $sum
        ]);
    }

    public function importupdate(Request $request)
    {
        $id = $request->dataId;
        $value = $request->value;
        $import = Import::find($id);
        if ($value !== null) {
            $import->update([
                'quantity' => $value,
                'total' =>  $import->price * $value,
            ]);
        } else {
            $import->update([
                'quantity' => $value,
                'total' =>  null,
            ]);
        }
        $imports = Import::get();
        $sum = 0;
        foreach ($imports as $item) {
            $sum += $item->total;
        }
        return response()->json([
            'import' => $imports,
            'total' => $sum
        ]);
    }

    public function importupdateprice(Request $request)
    {
        $id = $request->dataId;
        $value = $request->value;
        $import = Import::find($id);
        if ($value !== null) {
            $import->update([
                'price' => $value,
                'total' =>  $import->quantity * $value,
            ]);
        } else {
            $import->update([
                'price' => $value,
                'total' =>  null,
            ]);
        }
        $imports = Import::get();
        $sum = 0;
        foreach ($imports as $item) {
            $sum += $item->total;
        }
        return response()->json([
            'import' => $imports,
            'total' => $sum
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
            'total' => $sum
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
            'total' => $sum
        ]);
    }

    public function addCategory(Request $request)
    {
        $list_id = $request->selectedValues;
        $imports = Import::get();
        foreach ($list_id as $item) {
            $product = $this->productService->getProductByCategory($item);
            foreach ($product as $key => $value) {

                if (!$imports->contains('product_id', $value->id)) {
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
            'total' => $sum
        ]);
    }
}
