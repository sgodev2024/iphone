<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Storage;
use App\Services\StorageService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Gate;
use App\Models\ProductImei;


class StorageController extends Controller
{
    protected $storageService;
    public function __construct(StorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    public function index(Request $request)
    {
        $title = 'Kho hàng';
    
        if (!$request->ajax()) {
            return view('admin.storage.index', [
                'title' => $title,
            ]);
        }
    
        $searchText = trim((string) $request->query('s'));
        $storageId = $request->integer('inventory');
    
        /*
        |--------------------------------------------------------------------------
        | Danh sách sản phẩm tồn kho
        |--------------------------------------------------------------------------
        */
        if ($storageId > 0) {
            Gate::authorize('storage.products');
    
            $storage = $this->storageQuery()
                ->findOrFail($storageId);
    
                $products = $storage->products()
                ->with([
                    'brand',
                    'category',
                ])
                ->withCount([
                    'imeis as storage_imei_stock_count' => function ($query) use ($storage) {
                        $query->where('storage_id', $storage->id)
                            ->where('status', ProductImei::STATUS_IN_STOCK);
                    },
                ])
                ->when($searchText !== '', function (Builder $query) use ($searchText) {
                    $query->where(function (Builder $query) use ($searchText) {
                        $query->where('products.name', 'like', "%{$searchText}%")
                            ->orWhere('products.code', 'like', "%{$searchText}%")
                            ->orWhere('products.barcode', 'like', "%{$searchText}%");
            
                        if (ctype_digit($searchText)) {
                            $query->orWhere('products.id', (int) $searchText);
                        }
                    });
                })
                ->orderByDesc('products.id')
                ->paginate(20)
                ->appends($request->query());
    
            $html = view('admin.storage.inventory', [
                'storage' => $storage,
                'products' => $products,
            ])->render();
    
            return response()->json([
                'html' => $html,
                'view' => 'inventory',
                'storage_id' => $storage->id,
            ]);
        }
    
        /*
        |--------------------------------------------------------------------------
        | Danh sách kho
        |--------------------------------------------------------------------------
        */
        $storages = $this->storageQuery()
            ->when($searchText !== '', function (Builder $query) use ($searchText) {
                $query->where(function (Builder $query) use ($searchText) {
                    $query->where('name', 'like', "%{$searchText}%")
                        ->orWhere('location', 'like', "%{$searchText}%");
    
                    if (ctype_digit($searchText)) {
                        $query->orWhere('id', (int) $searchText);
                    }
                });
            })
            ->latest()
            ->paginate(10)
            ->appends($request->query());
    
        $html = view('admin.storage.table', [
            'storages' => $storages,
        ])->render();
    
        return response()->json([
            'html' => $html,
            'view' => 'storages',
        ]);
    }

    public function show($id)
    {
        $storage = $this->storageQuery()->find($id);

        if (!$storage) return errorResponse('Không tìm thấy kho trên hệ thống!', Response::HTTP_NOT_FOUND);

        return successResponse(data: $storage, isToastr: false);
    }

    public function store(Request $request)
    {
        $credentials = $this->validateRequest($request);

        return transaction(function () use ($credentials) {
            $credentials['user_id'] = Auth::id();
            Storage::create($credentials);

            return successResponse('Tạo mới kho thành công.', code: Response::HTTP_CREATED);
        });
    }
    public function update(Request $request, string $id)
    {
        $credentials = $this->validateRequest($request, $id);

        return transaction(function () use ($credentials, $id) {

            if (!$storage = $this->storageQuery()->find($id)) return errorResponse('Không tìm thấy kho trên hệ thống!', Response::HTTP_NOT_FOUND);

            $storage->update($credentials);

            return successResponse('Cập nhật kho thành công.', code: Response::HTTP_OK);
        });
    }

    public function detail($id)
    {
        try {
            $storage = $this->storageService->getStorageById($id);
            $product = $this->storageService->getProductInStorage($id);
            return view('admin.storage.detail', compact('product', 'storage'));
        } catch (Exception $e) {
            Log::error('Failed to find Storage info: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch Storage info'], 500);
        }
    }

    private function validateRequest($request, $id = null)
    {
        return $request->validate([
            'name' => [
                'required',
                'max:255',
                Rule::unique('storages')
                    ->where(fn($q) => $this->applyStorageScope($q))
                    ->ignore($id),
            ],
            'location' => 'nullable|max:255',
        ], __('request.messages'), [
            'name' => 'Tên kho',
            'location' => 'Địa chỉ',
        ]);
    }

    private function storageQuery(): Builder
{
    return $this->applyStorageScope(
        Storage::query()
            ->withSum(
                'productStorages as total_quantity',
                'quantity'
            )
    );
}

    public function inventory(Storage $storage)
    {
        $products = $storage->products()
            ->with([
                'brand',
                'category',
            ])
            ->paginate(20);

        return view(
            'admin.storage.inventory',
            compact('storage', 'products')
        );
    }

    private function applyStorageScope($query)
    {
        $user = Auth::user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        $ownerIds = collect([$user->id, $user->manager_id])
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $query->where(function ($query) use ($ownerIds, $user) {
            if (!empty($ownerIds)) {
                $query->whereIn('user_id', $ownerIds);
            }

            if ($user->storage_id) {
                $query->orWhere('id', (int) $user->storage_id);
            }
        });
    }

    public function productImeis(
        int $storage,
        int $product
    ) {
        Gate::authorize('product.imei.view');
    
        /*
         * Kiểm tra kho thuộc phạm vi người dùng hiện tại.
         */
        $storageModel = $this->storageQuery()
            ->findOrFail($storage);
    
        /*
         * Kiểm tra sản phẩm thực sự thuộc kho đang xem.
         */
        $productModel = $storageModel->products()
            ->where('products.id', $product)
            ->firstOrFail();
    
        if (! $productModel->isImeiTracked()) {
            return response()->json([
                'message' => 'Sản phẩm này không quản lý theo IMEI.',
            ], 422);
        }
    
        $imeis = ProductImei::query()
            ->where('product_id', $productModel->id)
            ->where('storage_id', $storageModel->id)
            ->latest()
            ->get();
    
        $html = view('admin.storage.imeis', [
            'storage' => $storageModel,
            'product' => $productModel,
            'imeis' => $imeis,
        ])->render();
    
        return response()->json([
            'html' => $html,
        ]);
    }
}
