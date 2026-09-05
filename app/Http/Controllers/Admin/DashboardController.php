<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function __construct(private BranchContext $branchContext) {}
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
        $returnStats = $this->getReturnStats();
        return view('welcome', compact(
            'stats',
            'orderStats',
            'totalRevenueStats',
            'inventoryStats',
            'aovStats',
            'topSellingProducts',
            'lowStockProducts',
            'latestOrders',
            'newCustomers',
            "returnStats"
        ));
    }

    private function getRevenueStats(): array
    {
        $actor = request()->user();
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        /*
        |--------------------------------------------------------------------------
        | DOANH THU BÁN HÀNG THỰC
        |--------------------------------------------------------------------------
        |
        | Loại các order được tạo ra từ nghiệp vụ đổi hàng.
        |
        */
    
        $salesQuery = DB::table('orders as o');
        $this->scopeBusinessQuery($salesQuery, $actor, 'o.branch_id', 'o.user_id');
        $sales = $salesQuery->where('o.status', 1)
    
            ->whereNotExists(function ($query) {
                $query
                    ->select(DB::raw(1))
                    ->from('order_returns as r')
                    ->whereColumn(
                        'r.exchange_order_id',
                        'o.id'
                    )
                    ->where(
                        'r.status',
                        'completed'
                    );
            })
    
            ->selectRaw("
                SUM(
                    CASE
                        WHEN DATE(o.created_at) = ?
                        THEN o.total_money
                        ELSE 0
                    END
                ) AS today_sales,

                SUM(
                    CASE
                        WHEN DATE(o.created_at) = ?
                        THEN o.total_money
                        ELSE 0
                    END
                ) AS yesterday_sales
            ", [$today, $yesterday])
    
            ->first();
    
    
        /*
        |--------------------------------------------------------------------------
        | ĐỔI / TRẢ HÀNG
        |--------------------------------------------------------------------------
        |
        | refund_amount:
        |   tiền thực tế trả lại khách.
        |
        | additional_payment:
        |   tiền khách trả thêm.
        |
        | additional_payment hiện đã bao gồm fee_amount,
        | nên KHÔNG cộng fee_amount lần nữa.
        |
        */
    
        $returnsQuery = DB::table('order_returns');
        $this->scopeBusinessQuery($returnsQuery, $actor, 'branch_id', 'user_id');
        $returns = $returnsQuery->where('status', 'completed')
    
            ->selectRaw("
                SUM(
                    CASE
                        WHEN DATE(created_at) = ?
                        THEN refund_amount
                        ELSE 0
                    END
                ) AS today_refund,

                SUM(
                    CASE
                        WHEN DATE(created_at) = ?
                        THEN additional_payment
                        ELSE 0
                    END
                ) AS today_additional_payment,

                SUM(
                    CASE
                        WHEN DATE(created_at) = ?
                        THEN refund_amount
                        ELSE 0
                    END
                ) AS yesterday_refund,

                SUM(
                    CASE
                        WHEN DATE(created_at) = ?
                        THEN additional_payment
                        ELSE 0
                    END
                ) AS yesterday_additional_payment
            ", [$today, $today, $yesterday, $yesterday])
    
            ->first();
    
    
        /*
        |--------------------------------------------------------------------------
        | DOANH THU THUẦN
        |--------------------------------------------------------------------------
        */
    
        $todayRevenue =
            (float) ($sales->today_sales ?? 0)
            - (float) ($returns->today_refund ?? 0)
            + (float) ($returns->today_additional_payment ?? 0);
    
    
        $yesterdayRevenue =
            (float) ($sales->yesterday_sales ?? 0)
            - (float) ($returns->yesterday_refund ?? 0)
            + (float) ($returns->yesterday_additional_payment ?? 0);
    
    
        /*
        |--------------------------------------------------------------------------
        | SO SÁNH HÔM QUA
        |--------------------------------------------------------------------------
        */
    
        $percentChange = null;
    
        if ($yesterdayRevenue > 0) {
    
            $percentChange = round(
                (
                    $todayRevenue
                    -
                    $yesterdayRevenue
                )
                /
                $yesterdayRevenue
                *
                100,
                2
            );
        }
    
    
        return [
            'today_revenue'
                => $todayRevenue,
    
            'yesterday_revenue'
                => $yesterdayRevenue,
    
            'percent_change'
                => $percentChange,
        ];
    }

    private function getOrderStats(): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $query = DB::table('orders');
        $this->scopeBusinessQuery($query, request()->user());
        $orders = $query
            ->selectRaw("
            COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) AS today_orders,
            COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) AS yesterday_orders
        ", [$today, $yesterday])
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
        $revenueQuery = DB::table('orders as o');
        $this->scopeBusinessQuery($revenueQuery, request()->user(), 'o.branch_id', 'o.user_id');
        $totalRevenue = $revenueQuery->where('o.status', 1)
            ->whereBetween(DB::raw('DATE(o.created_at)'), [$startDate, $endDate])
            ->sum('o.total_money');

        // Tổng giá vốn
        $costQuery = DB::table('orders as o');
        $this->scopeBusinessQuery($costQuery, request()->user(), 'o.branch_id', 'o.user_id');
        $totalCost = $costQuery->join('order_details as oi', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->leftJoin('product_imeis as pi', 'oi.product_imei_id', '=', 'pi.id')
            ->leftJoin('import_detail as id', 'pi.import_detail_id', '=', 'id.id')
            ->where('o.status', 1)
            ->whereBetween(DB::raw('DATE(o.created_at)'), [$startDate, $endDate])
            ->sum(DB::raw('COALESCE(id.price, p.price_buy) * oi.quantity'));

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
        $stockQuery = DB::table('product_storage as ps')
            ->join('storages as s', 's.id', '=', 'ps.storage_id')
            ->join('products as p', 'p.id', '=', 'ps.product_id')
            ->where('p.status', 1);
        $this->scopeInventoryQuery($stockQuery, request()->user());
        $totalStock = $stockQuery->sum('ps.quantity');

        // Số sản phẩm sắp hết
        $lowStockQuery = DB::table('product_storage as ps')
            ->join('storages as s', 's.id', '=', 'ps.storage_id')
            ->join('products as p', 'p.id', '=', 'ps.product_id')
            ->where('p.status', 1);
        $this->scopeInventoryQuery($lowStockQuery, request()->user());
        $lowStockCount = $lowStockQuery
            ->groupBy('p.id')
            ->havingRaw('SUM(ps.quantity) <= ?', [$lowStockThreshold])
            ->get(['p.id'])
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

        $currentQuery = DB::table('orders');
        $this->scopeBusinessQuery($currentQuery, request()->user());
        $currentData = $currentQuery->selectRaw('SUM(total_money) as revenue, COUNT(id) as orders')
            ->where('status', 1)
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->first();

        $lastMonthQuery = DB::table('orders');
        $this->scopeBusinessQuery($lastMonthQuery, request()->user());
        $lastMonthData = $lastMonthQuery->selectRaw('SUM(total_money) as revenue, COUNT(id) as orders')
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

        $this->scopeBusinessQuery($query, request()->user(), 'o.branch_id', 'o.user_id');

        // Nếu có thời gian lọc
        if ($startDate && $endDate) {
            $query->whereBetween(DB::raw('DATE(o.created_at)'), [$startDate, $endDate]);
        }

        return $query->limit($limit)->get()->toArray();
    }

    private function getLowStockProducts($lowStockThreshold = 5, $limit = 5): array
    {
        $query = DB::table('product_storage as ps')
            ->join('storages as s', 's.id', '=', 'ps.storage_id')
            ->join('products as p', 'p.id', '=', 'ps.product_id')
            ->select('p.name', DB::raw('SUM(ps.quantity) as quantity'))
            ->where('p.status', 1)
            ->groupBy('p.id', 'p.name')
            ->orderBy('quantity', 'asc');
        $this->scopeInventoryQuery($query, request()->user());

        return $query
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
        $query = DB::table('orders as o')
            ->leftJoin('users as u', 'o.user_id', '=', 'u.id')
            ->leftJoin('clients as c', 'o.client_id', '=', 'c.id')
            ->select(
                'o.id as order_id',
                DB::raw("COALESCE(o.name, c.name, u.name, 'Khách lạ') as customer_name"),
                'o.total_money',
                DB::raw("'unknown' as payment_method")
            )
            ->orderByDesc('o.created_at');
        $this->scopeBusinessQuery($query, request()->user(), 'o.branch_id', 'o.user_id');

        return $query
            ->limit($limit)
            ->get()
            ->each(fn ($order) => $order->order_code = 'DH'.str_pad(
                (string) $order->order_id,
                6,
                '0',
                STR_PAD_LEFT
            ))
            ->toArray();
    }
    private function getNewCustomers($startDate = null, $endDate = null): array
    {
        // Lấy khách hàng có đơn đầu tiên trong khoảng startDate -> endDate
        $firstOrdersQuery = DB::table('orders as o')
            ->select('o.client_id', DB::raw('MIN(o.created_at) as first_order_date'))
            ->groupBy('o.client_id');
        $this->scopeBusinessQuery($firstOrdersQuery, request()->user(), 'o.branch_id', 'o.user_id');

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

    private function getReturnStats(): array
    {
        /*
     |--------------------------------------------------------------------------
     | SỐ ĐƠN GỐC CÓ PHÁT SINH ĐỔI / TRẢ
     |--------------------------------------------------------------------------
     |
     | Một đơn có thể:
     |
     | ODR001
     |   ├── RTN001
     |   ├── RTN002
     |   └── RTN003
     |
     | Nhưng dashboard chỉ tính là:
     |
     | 1 đơn hàng có phát sinh hoàn trả.
     |
     */

        $returnedOrdersQuery = DB::table('order_returns');
        $this->scopeBusinessQuery($returnedOrdersQuery, request()->user(), 'branch_id', 'user_id');
        $returnedOrders = $returnedOrdersQuery->where('status', 'completed')
            ->distinct()
            ->count('original_order_id');


        /*
     |--------------------------------------------------------------------------
     | TỔNG ĐƠN BÁN THỰC
     |--------------------------------------------------------------------------
     |
     | Không tính order được sinh ra từ nghiệp vụ exchange.
     |
     | Ví dụ:
     |
     | ODR001 → RTN001 → ODR050
     |
     | ODR050 là đơn thay thế, không nên làm mẫu số tăng thêm.
     |
     | Dùng exchange_order_id để nhận diện thay vì dựa vào
     | payment_method = exchange.
     |
     */

        $saleOrdersQuery = DB::table('orders as o');
        $this->scopeBusinessQuery($saleOrdersQuery, request()->user(), 'o.branch_id', 'o.user_id');
        $totalSaleOrders = $saleOrdersQuery->where('o.status', 1)

            ->whereNotExists(function ($query) {
                $query
                    ->select(DB::raw(1))
                    ->from('order_returns as r')
                    ->whereColumn(
                        'r.exchange_order_id',
                        'o.id'
                    )
                    ->where(
                        'r.status',
                        'completed'
                    );
            })

            ->count();


        /*
     |--------------------------------------------------------------------------
     | TỶ LỆ ĐƠN CÓ HOÀN TRẢ
     |--------------------------------------------------------------------------
     */

        $returnRate = $totalSaleOrders > 0
            ? round(
                ($returnedOrders / $totalSaleOrders) * 100,
                1
            )
            : 0;


        /*
     |--------------------------------------------------------------------------
     | THỐNG KÊ CHI TIẾT
     |--------------------------------------------------------------------------
     |
     | Card hiện tại chưa cần dùng hết.
     | Nhưng chuẩn bị sẵn để sau này làm trang báo cáo đổi / trả.
     |
     */

        $financialQuery = DB::table('order_returns');
        $this->scopeBusinessQuery($financialQuery, request()->user(), 'branch_id', 'user_id');
        $financialStats = $financialQuery->where('status', 'completed')
            ->selectRaw('
            COUNT(*) AS return_transactions,

            SUM(
                CASE
                    WHEN exchange_order_id IS NULL
                    THEN 1
                    ELSE 0
                END
            ) AS pure_return_transactions,

            SUM(
                CASE
                    WHEN exchange_order_id IS NOT NULL
                    THEN 1
                    ELSE 0
                END
            ) AS exchange_transactions,

            COALESCE(SUM(return_amount), 0)
                AS return_amount,

            COALESCE(SUM(exchange_amount), 0)
                AS exchange_amount,

            COALESCE(SUM(fee_amount), 0)
                AS fee_amount,

            COALESCE(SUM(refund_amount), 0)
                AS refund_amount,

            COALESCE(SUM(additional_payment), 0)
                AS additional_payment
        ')
            ->first();


        return [
            /*
         * Card dashboard đang dùng.
         */
            'returned_orders'
            => (int) $returnedOrders,

            'total_sale_orders'
            => (int) $totalSaleOrders,

            'return_rate'
            => (float) $returnRate,


            /*
         * Dùng cho báo cáo sau này.
         */
            'return_transactions'
            => (int) (
                $financialStats->return_transactions
                ?? 0
            ),

            'pure_return_transactions'
            => (int) (
                $financialStats->pure_return_transactions
                ?? 0
            ),

            'exchange_transactions'
            => (int) (
                $financialStats->exchange_transactions
                ?? 0
            ),

            'return_amount'
            => (int) (
                $financialStats->return_amount
                ?? 0
            ),

            'exchange_amount'
            => (int) (
                $financialStats->exchange_amount
                ?? 0
            ),

            'fee_amount'
            => (int) (
                $financialStats->fee_amount
                ?? 0
            ),

            'refund_amount'
            => (int) (
                $financialStats->refund_amount
                ?? 0
            ),

            'additional_payment'
            => (int) (
                $financialStats->additional_payment
                ?? 0
            ),
        ];
    }

    private function scopeInventoryQuery($query, User $actor): void
    {
        if ($this->branchContext->isGlobal($actor)) {
            return;
        }

        $query->where('s.branch_id', $this->branchContext->branchId($actor));

        if (! $actor->isStaff()) {
            return;
        }

        if ($actor->storage_id === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where('s.id', (int) $actor->storage_id);
    }

    private function scopeBusinessQuery(
        $query,
        User $actor,
        string $branchColumn = 'branch_id',
        string $ownerColumn = 'user_id'
    ): void {
        if ($this->branchContext->isGlobal($actor)) {
            return;
        }

        $query->whereIn($ownerColumn, $this->ownerUserIds($actor));
        $this->branchContext->scope($query, $actor, $branchColumn);
    }

    private function ownerUserIds(User $actor): array
    {
        $ownerId = (int) $actor->ownerId();

        return \App\Models\User::query()
            ->whereKey($ownerId)
            ->orWhere('manager_id', $ownerId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->push((int) $actor->id)
            ->unique()
            ->values()
            ->all();
    }
}
