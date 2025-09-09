<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Config;
use App\Services\CategoryService;
use App\Services\CheckInventoryService;
use App\Services\ProductService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Categories;
use App\Models\CheckDetail;
use App\Models\warehome;

class CheckInventoryController extends Controller
{
    //
    protected $checkInventoryService;
    protected $productService;
    protected $categoryService;
    public function __construct(CheckInventoryService $checkInventoryService, ProductService $productService, CategoryService $categoryService)
    {
        $this->checkInventoryService = $checkInventoryService;
        $this->productService = $productService;
        $this->categoryService = $categoryService;
    }

    public function index()
    {
        try {
            $title = 'Kiểm kho';
            $config = Config::first();
            $user = Auth::user();
            $inventory = $this->checkInventoryService->getAllCheckInventory();
            return view('Themes.pages.Inventory.index', compact('inventory', 'config', 'title'));
        } catch (Exception $e) {
            Log::error('Failed to fetch inventory: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch inventory', 500);
        }
    }

    public function add()
    {
        $title = 'Kiểm kho';
        $config = Config::first();
        $user = Auth::user();
        $category = Categories::all();
        $product = $this->productService->getProductAll_Staff();
        return view('Themes.pages.Inventory.add', compact('config', 'user', 'product', 'category', 'title'));
    }
    public function submitadd(Request $request)
    {
        try {

            $user = Auth::user();
            $data = [
                'user_id' => $user->id,
                'note' => $request->note,
                'makho' => $request->makho
            ];
            $warehomes = warehome::whereNotNull('reality')->get();

            $inventory =  $this->checkInventoryService->addCheckInventory($data);
            foreach ($warehomes as  $value) {

                CheckDetail::create([
                    'check_inventory_id' => $inventory->id,
                    'product_id' =>  $value->product_id,
                    'difference' => $value->difference,
                    'gia_chenh_lech' => $value->gia_chenh_lech,

                ]);
            }
            warehome::truncate();
            return redirect()->route('staff.Inventory.get');
        } catch (Exception $e) {
            Log::error('Failed to fetch add inventory: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch add inventory', 500);
        }
    }
}
