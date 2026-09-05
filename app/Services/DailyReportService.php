<?php

namespace App\Services;

use App\Models\ImportCoupon;
use App\Models\ImportDetail;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Support\BranchContext;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DailyReportService
{
    protected $order;
    protected $import;
    public function __construct(Order $order, ImportCoupon $import, protected BranchContext $branchContext)
    {
        $this->order = $order;
        $this->import = $import;
    }

    public function getDailyOrder(?User $actor = null)
    {
        try {
            $actor ??= Auth::user();
            if (! $actor) {
                throw new Exception('Authenticated actor is required.');
            }
            // Paginate orders
            $ordersQuery = $this->order
                ->newQuery()
                ->where('status', 1)
                ->whereDate('created_at', now()->toDateString())
                ->with(['user', 'client']);
            $this->branchContext->scope($ordersQuery, $actor);
            if ($actor->isStaff()) {
                $actor->storage_id === null
                    ? $ordersQuery->whereRaw('1 = 0')
                    : $ordersQuery->whereHas('orderDetails', fn ($query) => $query->where('storage_id', (int) $actor->storage_id));
            }
            $orders = $ordersQuery
                ->latest('created_at')
                ->paginate(9, ['*'], 'orders_page');

            // Retrieve all order details for today
            $orderDetailsQuery = OrderDetail::whereHas('order', function ($query) use ($actor) {
                $query->where('status', 1)
                    ->whereDate('created_at', now()->toDateString());
                $this->branchContext->scope($query, $actor);
            });
            if ($actor->isStaff()) {
                $actor->storage_id === null
                    ? $orderDetailsQuery->whereRaw('1 = 0')
                    : $orderDetailsQuery->where('storage_id', (int) $actor->storage_id);
            }
            $orderDetails = $orderDetailsQuery->with(['product', 'order.orderDetails'])->get();

            // Calculate product sales
            $productSales = [];
            foreach ($orderDetails as $orderDetail) {
                if (!$orderDetail->product) {
                    continue;
                }

                $productId = $orderDetail->product_id;
                $quantity = $orderDetail->quantity;
                $lineTotal = (float) $orderDetail->price * $quantity;
                $orderSubtotal = (float) $orderDetail->order?->orderDetails->sum(
                    fn ($detail) => (float) $detail->price * (int) $detail->quantity
                );
                $total = $orderSubtotal > 0
                    ? (float) $orderDetail->order->total_money * ($lineTotal / $orderSubtotal)
                    : 0;

                if (!isset($productSales[$productId])) {
                    $productSales[$productId] = [
                        'quantity' => 0,
                        'total' => 0
                    ];
                }

                $productSales[$productId]['quantity'] += $quantity;
                $productSales[$productId]['total'] += $total;
            }

            // Get the products and paginate product sales
            $products = Product::whereIn('id', array_keys($productSales))->get()->keyBy('id');

            // Paginate the product sales
            $perPage = 9; // Number of items per page
            $currentPage = LengthAwarePaginator::resolveCurrentPage('products_page');
            $currentResults = array_slice($productSales, ($currentPage - 1) * $perPage, $perPage, true);

            $productSalesPaginated = new LengthAwarePaginator(
                $currentResults,
                count($productSales),
                $perPage,
                $currentPage,
                ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'products_page']
            );

            return [
                'orders' => $orders,
                'productSales' => $productSalesPaginated,
                'products' => $products
            ];
        } catch (Exception $e) {
            Log::error("Failed to get today's orders: " . $e->getMessage());
            throw new Exception("Failed to get today's orders");
        }
    }


    public function getDailyImport(?User $actor = null)
    {
        try {
            $actor ??= Auth::user();
            if (! $actor) {
                throw new Exception('Authenticated actor is required.');
            }
            // Paginate imports
            $importsQuery = $this->import
                ->newQuery()
                ->whereDate('created_at', now()->toDateString())
                ->with('details.product');
            $this->branchContext->scopeThroughStorage($importsQuery, $actor);
            $imports = $importsQuery
                ->reorder()
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(3, ['*'], 'import_page');

            // Retrieve all import details for today
            $importDetails = ImportDetail::whereHas('import', function ($query) use ($actor) {
                $query->whereDate('created_at', now()->toDateString());
                $this->branchContext->scopeThroughStorage($query, $actor);
            })->with('product')->get();

            // Calculate product imports
            $productImports = [];
            foreach ($importDetails as $importDetail) {
                $productId = $importDetail->product_id;
                $quantity = $importDetail->quantity;
                $price = $importDetail->price;
                $oldPrice = $importDetail->old_price;
                $total = $price * $quantity;

                if (!isset($productImports[$productId])) {
                    $productImports[$productId] = [
                        'quantity' => 0,
                        'total' => 0,
                        'price' => 0,
                        'old_price' => 0,
                    ];
                }

                $productImports[$productId]['quantity'] += $quantity;
                $productImports[$productId]['total'] += $total;
                $productImports[$productId]['price'] = $price; // Gán giá mới cho sản phẩm
                $productImports[$productId]['old_price'] = $oldPrice; // Gán giá cũ cho sản phẩm
            }

            // Get the products and paginate product imports
            $products = Product::whereIn('id', array_keys($productImports))->get()->keyBy('id');

            $perPage = 5; // Number of items per page
            $currentPage = LengthAwarePaginator::resolveCurrentPage('products_page');
            $currentResults = array_slice($productImports, ($currentPage - 1) * $perPage, $perPage, true);

            $productImportsPaginated = new LengthAwarePaginator(
                $currentResults,
                count($productImports),
                $perPage,
                $currentPage,
                ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'products_page']
            );

            return [
                'imports' => $imports,
                'productImports' => $productImportsPaginated,
                'products' => $products
            ];
        } catch (Exception $e) {
            Log::error("Failed to get today's importation: " . $e->getMessage());
            throw new Exception("Failed to get today's importation");
        }
    }
}
