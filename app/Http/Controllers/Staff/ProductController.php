<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\Client;
use App\Models\Config;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\ProductStorage;
use App\Services\ClientGroupService;
use App\Services\ClientService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    //
    protected $productService;
    protected $clientService;
    protected $clientGroupService;
    public function __construct(ProductService $productService, ClientService $clientService, ClientGroupService $clientGroupService)
    {
        $this->productService = $productService;
        $this->clientService = $clientService;
        $this->clientGroupService = $clientGroupService;
    }
    public function index()
    {
        $user = Auth::user();

        $title = "Quản lý bán hàng";
        $config = Config::with(['bank', 'user'])->first();
        $missingConfigMessage = !$config
            ? 'Chưa cấu hình thông tin chuyển khoản.'
            : (!$config->bank ? 'Chưa cấu hình ngân hàng cho thông tin chuyển khoản.' : null);
        $clientgroup = $this->clientGroupService->getAllClientGroup();
        $cart =  Cart::where('user_id', $user->id)->get();
        foreach ($cart as $key => $item) {
            $item->delete();
        }
        $sum = 0;

        foreach ($cart as $key => $value) {
            $sum += $value->price * $value->amount;
        }

        return view('Themes.pages.layout_staff.index', compact('cart', 'sum', 'config', 'title', 'clientgroup', 'missingConfigMessage'));
    }

    public function getBranchs()
    {
        $branchs = Branch::query()->where('status', true)->pluck('name', 'id')->toArray();

        return response()->json($branchs);
    }

    public function product(Request $request)
    {
        $user = Auth::user();
        $storageId = $user->storage_id;

        if (!$storageId) {
            return response()->json([
                'message' => 'Nhân viên chưa được gán kho bán hàng.',
            ], 422);
        }

        $storageId = (int) $storageId;
        $searchText = trim((string) $request->input('searchText', ''));
        if ($searchText === '') {
            $searchText = trim((string) $request->input('search', ''));
        }

        $products = Product::query()
            ->select([
                'products.id',
                'products.user_id',
                'products.code',
                'products.barcode',
                'products.name',
                'products.price_buy',
                'products.thumbnail',
                'products.product_unit',
                'products.inventory_tracking',
            ])
            ->selectSub(
                ProductStorage::query()
                    ->selectRaw('COALESCE(SUM(product_storage.quantity), 0)')
                    ->whereColumn('product_storage.product_id', 'products.id')
                    ->where('product_storage.storage_id', $storageId),
                'quantity_stock'
            )
            ->selectSub(
                ProductImei::query()
                    ->selectRaw('COUNT(*)')
                    ->join('import_detail', 'import_detail.id', '=', 'product_imeis.import_detail_id')
                    ->join('import_coupon', 'import_coupon.id', '=', 'import_detail.import_id')
                    ->whereColumn('product_imeis.product_id', 'products.id')
                    ->where('product_imeis.status', ProductImei::STATUS_IN_STOCK)
                    ->where('import_coupon.storage_id', $storageId)
                    ->whereNull('product_imeis.deleted_at'),
                'imei_stock'
            )
            ->where(function ($query) {
                $query->where('products.status', true)
                    ->orWhere('products.status', 1)
                    ->orWhere('products.status', '1')
                    ->orWhere('products.status', 'published');
            })
            ->where(function ($query) use ($storageId) {
                $query
                    ->where(function ($query) use ($storageId) {
                        $query
                            ->where(function ($query) {
                                $query
                                    ->whereNull('products.inventory_tracking')
                                    ->orWhere('products.inventory_tracking', Product::INVENTORY_TRACKING_QUANTITY);
                            })
                            ->whereExists(function ($subQuery) use ($storageId) {
                                $subQuery
                                    ->selectRaw('1')
                                    ->from('product_storage')
                                    ->whereColumn('product_storage.product_id', 'products.id')
                                    ->where('product_storage.storage_id', $storageId)
                                    ->where('product_storage.quantity', '>', 0);
                            });
                    })
                    ->orWhere(function ($query) use ($storageId) {
                        $query
                            ->where('products.inventory_tracking', Product::INVENTORY_TRACKING_IMEI)
                            ->whereExists(function ($subQuery) use ($storageId) {
                                $subQuery
                                    ->selectRaw('1')
                                    ->from('product_imeis')
                                    ->join('import_detail', 'import_detail.id', '=', 'product_imeis.import_detail_id')
                                    ->join('import_coupon', 'import_coupon.id', '=', 'import_detail.import_id')
                                    ->whereColumn('product_imeis.product_id', 'products.id')
                                    ->where('product_imeis.status', ProductImei::STATUS_IN_STOCK)
                                    ->where('import_coupon.storage_id', $storageId)
                                    ->whereNull('product_imeis.deleted_at');
                            });
                    });
            })
            ->when($searchText !== '', function ($query) use ($searchText) {
                $query->where(function ($query) use ($searchText) {
                    $query
                        ->where('products.name', 'like', "%{$searchText}%")
                        ->orWhere('products.code', 'like', "%{$searchText}%")
                        ->orWhere('products.barcode', 'like', "%{$searchText}%");
                });
            })
            ->orderBy('products.name')
            ->limit(30)
            ->get()
            ->map(function (Product $product) use ($storageId) {
                $inventoryTracking = $product->inventory_tracking
                    ?: Product::INVENTORY_TRACKING_QUANTITY;
                $isImeiProduct = $inventoryTracking === Product::INVENTORY_TRACKING_IMEI;
                $availableQuantity = $isImeiProduct
                    ? (int) $product->imei_stock
                    : (int) $product->quantity_stock;

                return [
                    'id' => (int) $product->id,
                    'product_id' => (int) $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'barcode' => $product->barcode,
                    'thumbnail' => $product->thumbnail,
                    'price_buy' => (float) $product->price_buy,
                    'quantity' => $availableQuantity,
                    'available_quantity' => $availableQuantity,
                    'storage_id' => $storageId,
                    'product_unit' => $product->product_unit,
                    'tracking_type' => $isImeiProduct
                        ? 'imei_product'
                        : Product::INVENTORY_TRACKING_QUANTITY,
                    'images' => $product->images,
                ];
            })
            ->values();

        $imeiDevices = collect();

        if ($searchText !== '') {
            $imeiDevices = ProductImei::query()
                ->with(['product', 'importDetail.import'])
                ->where('product_imeis.status', ProductImei::STATUS_IN_STOCK)
                ->whereHas('product', function ($query) {
                    $query
                        ->where('products.inventory_tracking', Product::INVENTORY_TRACKING_IMEI)
                        ->where(function ($query) {
                            $query->where('products.status', true)
                                ->orWhere('products.status', 1)
                                ->orWhere('products.status', '1')
                                ->orWhere('products.status', 'published');
                        });
                })
                ->whereHas('importDetail.import', function ($query) use ($storageId) {
                    $query->where('import_coupon.storage_id', $storageId);
                })
                ->whereDoesntHave('orderDetails')
                ->whereExists(function ($query) use ($storageId) {
                    $query
                        ->selectRaw('1')
                        ->from('product_storage')
                        ->whereColumn('product_storage.product_id', 'product_imeis.product_id')
                        ->where('product_storage.storage_id', $storageId)
                        ->where('product_storage.quantity', '>', 0);
                })
                ->where(function ($query) use ($searchText) {
                    $query
                        ->where('product_imeis.imei', $searchText)
                        ->orWhere('product_imeis.barcode', $searchText);

                    if (strlen($searchText) >= 4) {
                        $query->orWhere('product_imeis.imei', 'like', "%{$searchText}%");
                    }
                })
                ->orderByRaw(
                    'CASE WHEN product_imeis.imei = ? THEN 0 WHEN product_imeis.barcode = ? THEN 0 ELSE 1 END',
                    [$searchText, $searchText]
                )
                ->orderBy('product_imeis.imei')
                ->limit(30)
                ->get()
                ->map(function (ProductImei $imei) use ($storageId) {
                    $product = $imei->product;

                    if (! $product) {
                        return null;
                    }

                    return [
                        'id' => (int) $product->id,
                        'product_imei_id' => (int) $imei->id,
                        'product_id' => (int) $product->id,
                        'name' => $product->name,
                        'code' => $product->code,
                        'thumbnail' => $product->thumbnail,
                        'price_buy' => (float) $product->price_buy,
                        'imei' => $imei->imei,
                        'barcode' => $imei->barcode,
                        'quantity' => 1,
                        'available_quantity' => 1,
                        'storage_id' => $storageId,
                        'tracking_type' => Product::INVENTORY_TRACKING_IMEI,
                        'result_type' => 'imei_device',
                    ];
                })
                ->filter()
                ->values();
        }

        return response()->json(
            $imeiDevices
                ->concat($products)
                ->take(30)
                ->values()
        );
    }

    public function getClients(Request $request)
    {
        $user = Auth::user();

        $userId = $user->role_id === 3 ? $user->manager_id : $user->id;

        $searchText = $request->input('searchText');
        $clients = Client::query()
            ->where('user_id', $userId)
            ->when(!empty($searchText), function ($query) use ($searchText) {
                $query->where(function ($query) use ($searchText) {
                    $query->where('name', 'like', "%{$searchText}%")
                        ->orWhere('phone', 'like', "%{$searchText}%");
                });
            })
            ->get();
        return response()->json($clients);
    }

    public function addToCart(Request $request)
    {
        $user = Auth::user();
        $storage_id = $user->storage_id;
        $productId = $request->input('product_id');
        $product = $this->productService->getProductById($productId);

        if (!$product) {
            return response()->json(['error' => 'Product not found.'], 404);
        }

        $user = Auth::user();
        $existingCartItem = Cart::where('product_id', $productId)
            ->where('user_id', $user->id)
            ->first();
        $amount = $request->input('amount');
        $ProductStorage = ProductStorage::where([
            ['product_id', '=', $productId],
            ['storage_id', '=', $storage_id]
        ])->with('product')->first();
        if ($existingCartItem) {
            if ($existingCartItem->amount < $ProductStorage->quantity) {
                $existingCartItem->update(['amount' => $existingCartItem->amount + 1]);
            }
        } else {

            Cart::create([
                'product_id' => $productId,
                'price' => $product->price_buy,
                'user_id' => $user->id,
                'amount' => $amount
            ]);
        }

        $cartItems = Cart::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        $products = [];
        $sum = 0;
        foreach ($cartItems as $item) {
            $sum += $item->amount * $item->price;
            $product = ProductStorage::where([
                ['product_id', '=', $item->product_id],
                ['storage_id', '=', $storage_id]
            ])->with('product')->first();

            if ($product) {
                $products[] = [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'amount' => $item->amount,
                    'price_buy' => $item->price,
                    'product_name' => $product->product->name,
                    'quantity' => $product->quantity,
                ];
            }
        }
        return response()->json(['success' => 'Product added to cart!', 'cart' => $products, 'sum' => number_format($sum)]);
    }


    public function updateCart(Request $request)
    {
        $user = Auth::user();
        $storage_id = $user->storage_id;
        $productId = $request->input('product_id');
        $product = $this->productService->getProductById($productId);

        if (!$product) {
            return response()->json(['error' => 'Product not found.'], 404);
        }

        $user = Auth::user();
        $existingCartItem = Cart::where('product_id', $productId)
            ->where('user_id', $user->id)
            ->first();
        $amount = $request->input('amount');

        $existingCartItem->update(['amount' => $amount]);

        $cartItems = Cart::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $products = [];
        $sum = 0;
        foreach ($cartItems as $item) {
            $sum += $item->amount * $item->price;
            $product = ProductStorage::where([
                ['product_id', '=', $item->product_id],
                ['storage_id', '=', $storage_id]
            ])->with('product')->first();
            if ($product) {
                $products[] = [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'amount' => $item->amount,
                    'price_buy' => $item->price,
                    'product_name' => $product->product->name,
                    'quantity' => $product->quantity,
                ];
            }
        }
        return response()->json(['success' => 'Product added to cart!', 'cart' => $products, 'sum' => number_format($sum)]);
    }

    public function removeFromCart(Request $request)
    {
        $user = Auth::user();
        $storage_id = $user->storage_id;
        $cart = $request->input('cart');
        Cart::find($cart)->delete();
        $cartItems = Cart::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        $products = [];
        $sum = 0;
        foreach ($cartItems as $item) {
            $sum += $item->amount * $item->price;
            $product = ProductStorage::where([
                ['product_id', '=', $item->product_id],
                ['storage_id', '=', $storage_id]
            ])->with('product')->first();
            if ($product) {
                $products[] = [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'amount' => $item->amount,
                    'price_buy' => $item->price,
                    'product_name' => $product->product->name,
                    'quantity' => $product->quantity,
                ];
            }
        }
        return response()->json(['success' => 'Product added to cart!', 'cart' => $products, 'sum' => number_format($sum)]);
    }

    public function search(Request $request)
    {
        $name = $request->input('name');

        $user = Auth::user();
        $storage_id = $user->storage_id;

        if (!$storage_id) {
            return response()->json([
                'message' => 'Nhân viên chưa được gán kho bán hàng.',
            ], 422);
        }

        $userId = $user->role_id === 3 ? ($user->manager_id ?? $user->id) : $user->id;

        $productStorages = ProductStorage::with('product')
            ->where('storage_id', $storage_id)
            ->where('quantity', '>', 0)
            ->whereHas('product', function ($query) use ($name, $userId) {
                $query->where('user_id', $userId)
                    ->where('inventory_tracking', Product::INVENTORY_TRACKING_QUANTITY)
                    ->where(function ($query) {
                        $query->where('status', true)
                            ->orWhere('status', 1)
                            ->orWhere('status', '1')
                            ->orWhere('status', 'published');
                    })
                    ->where('name', 'like', "%{$name}%");
            })
            ->orderByDesc('created_at')
            ->get();

        $products = [];

        foreach ($productStorages as $storage) {
            $product = $storage->product;

            $products[] = [
                'id' => $product->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'price_buy' => $product->price_buy,
                'quantity' => $storage->quantity,
                'available_quantity' => (int) $storage->quantity,
                'storage_id' => (int) $storage->storage_id,
                'product_unit' => $product->product_unit,
                'tracking_type' => $product->inventory_tracking,
                'barcode' => $product->barcode,
                'images' => $product->images
            ];
        }

        return response()->json($products);
    }

    public function updatePriceCart(Request $request)
    {

        $user = Auth::user();
        $storage_id = $user->storage_id;
        $existingCartItem = Cart::find($request->cart);
        $price = $request->price;

        $existingCartItem->update(['price' => $price]);

        $cartItems = Cart::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $products = [];
        $sum = 0;
        foreach ($cartItems as $item) {
            $sum += $item->amount * $item->price;
            $product = ProductStorage::where([
                ['product_id', '=', $item->product_id],
                ['storage_id', '=', $storage_id]
            ])->with('product')->first();
            if ($product) {
                $products[] = [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'amount' => $item->amount,
                    'price_buy' => $item->price,
                    'product_name' => $product->product->name,
                    'quantity' => $product->quantity,
                ];
            }
        }
        return response()->json(['success' => 'Product added to cart!', 'cart' => $products, 'sum' => number_format($sum)]);
    }
}
