<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\BranchContext;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;


class OrderService
{
    protected $order;
    protected $client;
    protected $user;
    public function __construct(
        Order $order,
        Client $client,
        User $user,
        private readonly BranchContext $branchContext
    )
    {
        $this->user = $user;
        $this->client = $client;
        $this->order = $order;
    }
    public function getTodayOrder()
    {
        try {
            $today = Carbon::today();
            return $this->order->orderByDesc('created_at')->where('created_at', $today)->paginate(10);
        } catch (Exception $e) {
            Log::error('Failed to get today order: ' . $e->getMessage());
            throw new Exception('Failed to get today order');
        }
    }
    public function getOrderAll()
    {
        try {
            return $this->order->orderByDesc('created_at')->paginate(10);
        } catch (Exception $e) {
            Log::error('Failed to retrieve orders: ' . $e->getMessage());
            throw new Exception('Failed to retrieve orders');
        }
    }

    public function filterOrder($startDate, $endDate, $phone)
    {
        try {
            $query = $this->order->query();

            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            if ($phone) {
                $query->whereHas('client', function ($query) use ($phone) {
                    $query->where('phone', $phone);
                });
            }

            $orders = $query->paginate(10);
            return $orders;
        } catch (Exception $e) {
            Log::error('Failed to retrieve orders by date range: ' . $e->getMessage());
            throw new Exception('Failed to retrieve orders by date range');
        }
    }
    public function getOrderByUser($id)
    {
        try {
            $orders = $this->order->where('user_id', $id)->get();
            return $orders;
        } catch (Exception $e) {
            Log::error('Failed to retrieve orders: ' . $e->getMessage());
            throw new Exception('Failed to retrieve orders');
        }
    }
    public  function  updateOrder($id)
    {
        try {
            $order = $this->order->find($id);
            return $order;
        } catch (Exception $e) {
            Log::error('Failed to retrieve orders: ' . $e->getMessage());
            throw new Exception('Failed to retrieve orders');
        }
    }

    public function getOrderAmount()
    {
        try {
            $number = $this->order->count();
            $total = $this->order->sum('total_money');

            $result = [
                'number' => $number,
                'total' => $total
            ];
            return $result;
        } catch (Exception $e) {
            Log::error('Failed to count order: ' . $e->getMessage());
            throw new Exception('Failed to count orders');
        }
    }

    public function getMonthlyRevenue()
    {
        $currentYear = date('Y');

        $monthlyRevenue = Order::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(total_money) as total')
        )
            ->whereYear('created_at', $currentYear)
            ->groupBy('year', 'month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $months = range(1, 12);
        $monthlyRevenueWithZeroes = [];
        foreach ($months as $month) {
            $monthlyRevenueWithZeroes[$month] = isset($monthlyRevenue[$month]) ? $monthlyRevenue[$month]->total : 0;
        }
        $totalAnnualRevenue = array_sum($monthlyRevenueWithZeroes);

        return [
            'monthlyRevenue' => array_values($monthlyRevenueWithZeroes),
            'totalAnnualRevenue' => $totalAnnualRevenue,
        ];
    }
    public function getTodayRevenueAndOrders()
    {
        $currentDate = date('Y-m-d');
        $todayData = Order::select(
            DB::raw('COUNT(*) as totalOrders'),
            DB::raw('SUM(total_money) as totalMoney')
        )
            ->whereDate('created_at', $currentDate)
            ->first();
        return [
            'totalOrders' => $todayData->totalOrders,
            'totalMoney' => $todayData->totalMoney,
        ];
    }

    public function getOrderNotification(?User $user = null)
    {
        try {
            if (! $user) {
                return collect();
            }

            // Notifications are optional layout data. A non-global account without
            // a branch must see an empty set instead of making unrelated pages fail.
            if (! $this->branchContext->isGlobal($user) && $user->branch_id === null) {
                return collect();
            }

            if (! Schema::hasColumn('orders', 'branch_id')
                && ! $this->branchContext->isGlobal($user)
            ) {
                return collect();
            }

            $query = Order::query()
                ->with('client')
                ->where('notification', 1);

            if (Schema::hasColumn('orders', 'branch_id')) {
                $this->branchContext->scope($query, $user);
            }

            return $query->orderByDesc('created_at')->get();
        } catch (Exception $e) {
            Log::error('Failed to find order with notification: ' . $e->getMessage());
            throw new Exception('Failed to find order with notification');
        }
    }
    public function getOrderbyID($id)
    {
        try {
            $order = $this->order->find($id);
            return $order;
        } catch (Exception $e) {
            Log::error('Failed to find order: ' . $e->getMessage());
            throw new Exception('Failed to find order');
        }
    }


    // public function getOrderbyPhone($phone)
    // {
    //     try {
    // $orders = $this->order->whereHas('client', function ($query) use ($phone) {
    //     $query->where('phone', $phone);
    // })->get();

    //         // if ($orders->isEmpty()) {
    //         //     throw new Exception("Orders not found");
    //         // }

    //         return $orders;
    //     } catch (Exception $e) {
    //         Log::error('Failed to find orders: ' . $e->getMessage());
    //         throw new Exception('Failed to find orders');
    //     }
    // }
}
