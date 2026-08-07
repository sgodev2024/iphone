<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Services\OrderService;
use App\Services\DashboardService;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    protected $dashboardService, $orderService;
    public function __construct(DashboardService $dashboardService, OrderService $orderService)
    {
        $this->dashboardService = $dashboardService;
        $this->orderService = $orderService;
    }
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate   = $request->input('end_date', now()->endOfMonth()->toDateString());

        $stats              = $this->getRevenueStats(); // Doanh thu hôm nay & hôm qua
        $orderStats         = $this->getOrderStats();   // Số đơn hôm nay & hôm qua
        $totalRevenueStats  = $this->getTotalRevenue($startDate, $endDate); // Doanh thu & biên LN gộp
        $inventoryStats     = $this->getInventoryStats();
        $aovStats           = $this->getAverageOrderValue($startDate, $endDate);
        $topSellingProducts = $this->getTopSellingProducts($startDate, $endDate);
        $lowStockProducts   = $this->getLowStockProducts();
        $latestOrders = $this->getLatestOrders();
        $newCustomers       = $this->getNewCustomers($startDate, $endDate);
        return view('welcome', compact(
            'stats',
            'orderStats',
            'totalRevenueStats',
            'inventoryStats',
            'aovStats',
            'topSellingProducts',
            'lowStockProducts',
            'latestOrders',
            'newCustomers'
        ));
    }


    private function getRevenueStats(): array
    {
        $doanhThu = DB::table('orders')
            ->selectRaw("
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total_money ELSE 0 END) AS today_revenue,
                SUM(CASE WHEN DATE(created_at) = CURDATE() - INTERVAL 1 DAY THEN total_money ELSE 0 END) AS yesterday_revenue
            ")
            ->where('status', 1) // status 1 = đã hoàn thành
            ->first();

        $percentChange = null;
        if ($doanhThu->yesterday_revenue > 0) {
            $percentChange = round(
                ($doanhThu->today_revenue - $doanhThu->yesterday_revenue) / $doanhThu->yesterday_revenue * 100,
                2
            );
        }

        return [
            'today_revenue'     => (float) $doanhThu->today_revenue,
            'yesterday_revenue' => (float) $doanhThu->yesterday_revenue,
            'percent_change'    => $percentChange,
        ];
    }

    private function getOrderStats(): array
    {
        $orders = DB::table('orders')
            ->selectRaw("
            COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) AS today_orders,
            COUNT(CASE WHEN DATE(created_at) = CURDATE() - INTERVAL 1 DAY THEN 1 END) AS yesterday_orders
        ")
            ->where('status', 1) // Chỉ lấy đơn đã hoàn thành
            ->first();

        $percentChange = null;
        if ($orders->yesterday_orders > 0) {
            $percentChange = round(
                ($orders->today_orders - $orders->yesterday_orders) / $orders->yesterday_orders * 100,
                2
            );
        }

        return [
            'today_orders'     => (int) $orders->today_orders,
            'yesterday_orders' => (int) $orders->yesterday_orders,
            'percent_change'   => $percentChange,
        ];
    }

    private function getTotalRevenue($startDate = null, $endDate = null)
    {

        // Tổng doanh thu
        $totalRevenue = DB::table('orders as o')
            ->join('order_details as oi', 'o.id', '=', 'oi.order_id')
            ->where('o.status', 1)
            ->whereBetween(DB::raw('DATE(o.created_at)'), [$startDate, $endDate])
            ->sum(DB::raw('oi.price * oi.quantity'));

        // Tổng giá vốn
        $totalCost = DB::table('orders as o')
            ->join('order_details as oi', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->where('o.status', 1)
            ->whereBetween(DB::raw('DATE(o.created_at)'), [$startDate, $endDate])
            ->sum(DB::raw('p.price_buy * oi.quantity'));

        // Biên LN gộp (%)
        $grossMargin = $totalRevenue > 0
            ? round((($totalRevenue - $totalCost) / $totalRevenue) * 100, 2)
            : null;

        return [
            'total_revenue' => (float) $totalRevenue,
            'gross_margin'  => $grossMargin,
        ];
    }

    private function getInventoryStats($lowStockThreshold = 5): array
    {
        // Tổng tồn kho (tính tổng quantity)
        $totalStock = DB::table('products')
            ->where('status', 1)
            ->sum('quantity');

        // Số sản phẩm sắp hết
        $lowStockCount = DB::table('products')
            ->where('status', 1)
            ->where('quantity', '<=', $lowStockThreshold)
            ->count();

        return [
            'total_stock'     => (int) $totalStock,
            'low_stock_count' => (int) $lowStockCount,
        ];
    }

    private function getAverageOrderValue($startDate = null, $endDate = null): array
    {

        $lastMonthStart = now()->subMonth()->startOfMonth()->toDateString();
        $lastMonthEnd   = now()->subMonth()->endOfMonth()->toDateString();

        $currentData = DB::table('orders')
            ->selectRaw('SUM(total_money) as revenue, COUNT(id) as orders')
            ->where('status', 1)
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->first();

        $lastMonthData = DB::table('orders')
            ->selectRaw('SUM(total_money) as revenue, COUNT(id) as orders')
            ->where('status', 1)
            ->whereBetween(DB::raw('DATE(created_at)'), [$lastMonthStart, $lastMonthEnd])
            ->first();

        $currentRevenue    = (float) ($currentData->revenue ?? 0);
        $currentOrders     = (int) ($currentData->orders ?? 0);
        $lastMonthRevenue  = (float) ($lastMonthData->revenue ?? 0);
        $lastMonthOrders   = (int) ($lastMonthData->orders ?? 0);

        $currentAOV = $currentOrders > 0 ? $currentRevenue / $currentOrders : 0;
        $lastMonthAOV = $lastMonthOrders > 0 ? $lastMonthRevenue / $lastMonthOrders : 0;

        $percentChange = null;
        if ($lastMonthAOV > 0) {
            $percentChange = round((($currentAOV - $lastMonthAOV) / $lastMonthAOV) * 100, 2);
        }

        return [
            'current_aov'    => $currentAOV,
            'percent_change' => $percentChange
        ];
    }

    private function getTopSellingProducts($startDate = null, $endDate = null, $limit = 5): array
    {
        $query = DB::table('orders as o')
            ->join('order_details as oi', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->select(
                'p.name',
                DB::raw('SUM(oi.quantity) as total_sold')
            )
            ->where('o.status', 1) // chỉ lấy đơn hoàn thành
            ->groupBy('p.id', 'p.name')
            ->orderByDesc('total_sold');

        // Nếu có thời gian lọc
        if ($startDate && $endDate) {
            $query->whereBetween(DB::raw('DATE(o.created_at)'), [$startDate, $endDate]);
        }

        return $query->limit($limit)->get()->toArray();
    }

    private function getLowStockProducts($lowStockThreshold = 5, $limit = 5): array
    {
        return DB::table('products')
            ->select('name', 'quantity')
            ->where('status', 1)
            ->orderBy('quantity', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($product) use ($lowStockThreshold) {
                $product->status_label = $product->quantity <= $lowStockThreshold ? 'Sắp hết' : 'Còn hàng';
                $product->status_class = $product->quantity <= $lowStockThreshold ? 'low-stock' : 'in-stock';
                return $product;
            })
            ->toArray();
    }
    private function getLatestOrders($limit = 6): array
    {
        return DB::table('orders as o')
            ->leftJoin('users as u', 'o.user_id', '=', 'u.id')
            ->leftJoin('clients as c', 'o.client_id', '=', 'c.id')
            ->select(
                'o.id as order_id',
                DB::raw("CONCAT('DH', LPAD(o.id, 6, '0')) as order_code"),
                DB::raw("COALESCE(o.name, c.name, u.name, 'Khách lạ') as customer_name"),
                'o.total_money',
                DB::raw("'unknown' as payment_method")
            )
            ->orderByDesc('o.created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }
    private function getNewCustomers($startDate = null, $endDate = null): array
    {
        // Lấy khách hàng có đơn đầu tiên trong khoảng startDate -> endDate
        $firstOrdersQuery = DB::table('orders as o')
            ->select('o.client_id', DB::raw('MIN(o.created_at) as first_order_date'))
            ->groupBy('o.client_id');

        $newCustomerIds = DB::table(DB::raw("({$firstOrdersQuery->toSql()}) as t"))
            ->mergeBindings($firstOrdersQuery)
            ->whereBetween(DB::raw('DATE(first_order_date)'), [$startDate, $endDate])
            ->pluck('client_id');

        $count = $newCustomerIds->count();

        // Hôm nay
        $todayCount = DB::table(DB::raw("({$firstOrdersQuery->toSql()}) as t"))
            ->mergeBindings($firstOrdersQuery)
            ->whereDate('first_order_date', now()->toDateString())
            ->count();
        // Hôm qua
        $yesterdayCount = DB::table(DB::raw("({$firstOrdersQuery->toSql()}) as t"))
            ->mergeBindings($firstOrdersQuery)
            ->whereDate('first_order_date', now()->subDay()->toDateString())
            ->count();

        $percentChange = null;
        if ($yesterdayCount > 0) {
            $percentChange = round(($todayCount - $yesterdayCount) / $yesterdayCount * 100, 2);
        }

        return [
            'total_new'      => $count,
            'today_new'      => $todayCount,
            'yesterday_new'  => $yesterdayCount,
            'percent_change' => $percentChange,
        ];
    }
}
