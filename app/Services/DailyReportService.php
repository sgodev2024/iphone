<?php

namespace App\Services;

use App\Models\ImportCoupon;
use App\Models\ImportDetail;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class DailyReportService
{
    protected $order;
    protected $import;
    public function __construct(Order $order, ImportCoupon $import)
    {
        $this->order = $order;
        $this->import = $import;
    }

    public function getDailyOrder()
    {
        try {
            // Paginate orders
            $orders = $this->order->whereDate('created_at', now()->toDateString())->paginate(3, ['*'], 'orders_page');

            // Retrieve all order details for today
            $orderDetails = OrderDetail::whereHas('order', function ($query) {
                $query->whereDate('created_at', now()->toDateString());
            })->with('product')->get();

            // Calculate product sales
            $productSales = [];
            foreach ($orderDetails as $orderDetail) {
                $productId = $orderDetail->product_id;
                $quantity = $orderDetail->quantity;
                $price = $orderDetail->product->price_buy;
                $total = $price * $quantity;

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
            $perPage = 5; // Number of items per page
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


    public function getDailyImport()
    {
        try {
            // Paginate imports
            $imports = $this->import->whereDate('created_at', now()->toDateString())
                ->with('details.product') // Eager load details and product
                ->paginate(3, ['*'], 'import_page');

            // Retrieve all import details for today
            $importDetails = ImportDetail::whereHas('import', function ($query) {
                $query->whereDate('created_at', now()->toDateString());
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
