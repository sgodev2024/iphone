<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\warehome;
use App\Models\Product;
use App\Models\ProductStorage;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\SaleStorageResolver;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Svg\Tag\Rect;

class WareHomeController extends Controller
{

    protected $categoryService;
    protected $productService;
    public function __construct(
        CategoryService $categoryService,
        ProductService $productService,
        private readonly SaleStorageResolver $saleStorageResolver
    )
    {
        $this->productService = $productService;
        $this->categoryService = $categoryService;
    }
    public function index(Request $request){
        $storageId = $this->saleStorageResolver->resolveSaleStorageId($request->user());
        $category = $this->categoryService->getCategoryAllStaff();
        $warehome = $this->stagedWarehomes($request, $storageId);
        return response()->json([
            'warehome' => $warehome,
            'category' => $category
        ]);
    }

    public function add(Request $request){
        $user = $request->user();
        $storageId = $this->saleStorageResolver->resolveSaleStorageId($user);
        $productId = $request->integer('product');
        ProductStorage::query()
            ->where('storage_id', $storageId)
            ->where('product_id', $productId)
            ->firstOrFail();
         $product = $this->stagingQuery($request)->where('product_id', $productId)->first();
         if(empty($product)){
            warehome::create([
                'product_id' => $productId,
                'user_id' => $user->id,
            ]);
         }
         $warehome = $this->stagedWarehomes($request, $storageId);
        return response()->json($warehome);

    }

    public function update(Request $request){
        $id = $request->input('dataId');
        $storageId = $this->saleStorageResolver->resolveSaleStorageId($request->user());
        $wareHouse = $this->stagingQuery($request)->findOrFail($id);
        $stockQuantity = (int) ProductStorage::query()
            ->where('storage_id', $storageId)
            ->where('product_id', $wareHouse->product_id)
            ->value('quantity');
        $reality = $request->input('value');
        if($reality == null){
            $wareHouse ->update([
                'reality' => null,
                'difference' => null,
                'gia_chenh_lech' => null
            ]);
        }else{
            $difference	 = $reality - $stockQuantity;
            $gia_chenh_lech	 = $reality * $wareHouse->product->price_buy - $stockQuantity * $wareHouse->product->price_buy;
            $wareHouse ->update([
                'reality' => $reality,
                'difference' => $difference,
                'gia_chenh_lech' => $gia_chenh_lech
            ]);
        }


        return response()->json([
            'reality' => $reality,
            'difference' => $difference ?? null,
            'gia_chenh_lech' => $gia_chenh_lech ?? null
        ]);

    }

    public function delete(Request $request){
        $id = $request->input('id');
        $storageId = $this->saleStorageResolver->resolveSaleStorageId($request->user());
        $warehome = $this->stagingQuery($request)->findOrFail($id);
        $warehome->delete();
        $warehomes = $this->stagedWarehomes($request, $storageId);
        return response()->json($warehomes);
    }

    public function addByCategory(Request $request){
        $list_id = $request->selectedValues;
        $storageId = $this->saleStorageResolver->resolveSaleStorageId($request->user());
        $warehome = $this->stagingQuery($request)->get();
        foreach($list_id as $item){
            $products = Product::query()
                ->where('category_id', $item)
                ->whereHas('productStorages', fn ($query) => $query->where('storage_id', $storageId))
                ->get();
            foreach ($products as $value) {

                if (!$warehome->contains('product_id', $value->id)) {
                    warehome::create([
                        'product_id' => $value->id,
                        'user_id' => $request->user()->id,
                    ]);
                }
            }
        }
        $warehomes = $this->stagedWarehomes($request, $storageId);
        return response()->json($warehomes);
    }

    public function checkwerehouse(Request $request){

        $this->saleStorageResolver->resolveSaleStorageId($request->user());
        $warehome = $this->stagingQuery($request)->get();
        if (!$warehome->isEmpty()) {
            $hasReality = $this->stagingQuery($request)->whereNotNull('reality')->exists();
            $result = $hasReality ? 1 : 2;
        } else {
            $result = 3;
        }

        return response()->json(['result' => $result]);
    }

    private function stagingQuery(Request $request)
    {
        return warehome::query()->where('user_id', $request->user()->id);
    }

    private function stagedWarehomes(Request $request, int $storageId)
    {
        $warehomes = $this->stagingQuery($request)->with('product')->get();
        $quantities = ProductStorage::query()
            ->where('storage_id', $storageId)
            ->whereIn('product_id', $warehomes->pluck('product_id')->all())
            ->pluck('quantity', 'product_id');

        $warehomes->each(function (warehome $item) use ($quantities): void {
            if ($item->product) {
                $item->product->setAttribute(
                    'quantity',
                    (int) ($quantities->get($item->product_id) ?? 0)
                );
            }
        });

        return $warehomes;
    }
}
