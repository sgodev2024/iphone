<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\warehome;
use App\Services\CategoryService;
use App\Services\ProductService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Svg\Tag\Rect;

class WareHomeController extends Controller
{

    protected $categoryService;
    protected $productService;
    public function __construct(CategoryService $categoryService, ProductService $productService)
    {
        $this->productService = $productService;
        $this->categoryService = $categoryService;
    }
    public function index(){
        $category = $this->categoryService->getCategoryAllStaff();
        $warehome = warehome::with('product')->get();
        return response()->json([
            'warehome' => $warehome,
            'category' => $category
        ]);
    }

    public function add(Request $request){
        $user = Auth::user();
        $productId = $request->input('product');
         $product = warehome::where(['product_id' => $productId, 'user_id' => $user->id])->first();
         if(empty($product)){
            warehome::create([
                'product_id' => $productId,
                'user_id' => $user->id,
            ]);
         }
         $warehome = warehome::with('product')->get();
        return response()->json($warehome);

    }

    public function update(Request $request){
        $id = $request->input('dataId');
        $wareHouse = warehome::find($id);
        $reality = $request->input('value');
        if($reality == null){
            $wareHouse ->update([
                'reality' => null,
                'difference' => null,
                'gia_chenh_lech' => null
            ]);
        }else{
            $difference	 = $reality -  $wareHouse->product->quantity;
            $gia_chenh_lech	 = $reality * $wareHouse->product->price_buy -  $wareHouse->product->quantity * $wareHouse->product->price_buy;
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
        $warehome = warehome::find($id);
        $warehome->delete();
        $warehomes = warehome::get();
        return response()->json($warehomes);
    }

    public function addByCategory(Request $request){
        $list_id = $request->selectedValues;
        $warehome = warehome::get();
        $user = Auth::user();
        foreach($list_id as $item){
            $product = $this->productService->getProductByCategory($item);
            foreach ($product as $key => $value) {

                if (!$warehome->contains('product_id', $value->id)) {
                    warehome::create([
                        'product_id' => $value->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        }
        $warehomes = warehome::get();
        return response()->json($warehomes);
    }

    public function checkwerehouse(){

        $warehome = warehome::get();
        if (!$warehome->isEmpty()) {
            $hasReality = warehome::whereNotNull('reality')->exists();
            $result = $hasReality ? 1 : 2;
        } else {
            $result = 3;
        }

        return response()->json(['result' => $result]);
    }
}
