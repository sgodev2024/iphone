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
use App\Models\Product;
use App\Services\SaleStorageResolver;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CheckInventoryController extends Controller
{
    //
    protected $checkInventoryService;
    protected $productService;
    protected $categoryService;
    public function __construct(
        CheckInventoryService $checkInventoryService,
        ProductService $productService,
        CategoryService $categoryService,
        private readonly SaleStorageResolver $saleStorageResolver
    )
    {
        $this->checkInventoryService = $checkInventoryService;
        $this->productService = $productService;
        $this->categoryService = $categoryService;
    }

    public function index()
    {
        try {
            $title = 'Kiểm kho';
            $config = Config::with(['bank', 'user'])->first();
            $user = Auth::user();
            $inventory = $this->checkInventoryService->getAllCheckInventory($user);
            return view('Themes.pages.Inventory.index', compact('inventory', 'config', 'title'));
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Failed to fetch inventory: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch inventory', 500);
        }
    }

    public function add()
    {
        $title = 'Kiểm kho';
        $config = Config::with(['bank', 'user'])->first();
        $user = Auth::user();
        $storageId = $this->saleStorageResolver->resolveSaleStorageId($user);
        $category = Categories::all();
        $product = Product::query()
            ->whereHas('productStorages', fn ($query) => $query->where('storage_id', $storageId))
            ->orderBy('name')
            ->get();
        return view('Themes.pages.Inventory.add', compact('config', 'user', 'product', 'category', 'title'));
    }
    public function submitadd(Request $request)
    {
        try {

            $user = Auth::user();
            $this->saleStorageResolver->resolveSaleStorageId($user);
            $data = [
                'user_id' => $user->id,
                'note' => $request->note,
                'makho' => $request->makho
            ];
            $warehomes = warehome::query()
                ->where('user_id', $user->id)
                ->whereNotNull('reality')
                ->get();

            $inventory = $this->checkInventoryService->addCheckInventory($data, $warehomes);
            foreach ($warehomes as  $value) {

                CheckDetail::create([
                    'check_inventory_id' => $inventory->id,
                    'product_id' =>  $value->product_id,
                    'difference' => $value->difference,
                    'gia_chenh_lech' => $value->gia_chenh_lech,

                ]);
            }
            warehome::query()->where('user_id', $user->id)->delete();
            return redirect()->route('staff.Inventory.get');
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Failed to fetch add inventory: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch add inventory', 500);
        }
    }
}
