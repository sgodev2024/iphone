<?php

namespace App\Services;

use App\Models\ImportCoupon;
use App\Models\ImportDetail;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductStorage;
use Exception;
use Illuminate\Support\Facades\Log;

class ProfitService
{
    protected $order;
    protected $orderDetail;
    protected $importCoupon;
    protected $importDetail;
    protected $product;
    protected $productStorage;

    public function __construct(Order $order, OrderDetail $orderDetail, ImportCoupon $importCoupon, ImportDetail $importDetail, Product $product, ProductStorage $productStorage)
    {
        $this->order = $order;
        $this->orderDetail = $orderDetail;
        $this->importCoupon = $importCoupon;
        $this->importDetail = $importDetail;
        $this->product = $product;
        $this->productStorage = $productStorage;
    }

    public function profitReport($period, $storage_id)
    {
        try {
            $report = [];

            // Retrieve all order IDs based on the storage_id
            $orderIds = $this->order->whereHas('user', function ($query) use ($storage_id) {
                $query->where('storage_id', $storage_id);
            })->pluck('id');
            Log::info($orderIds);
            // Helper function to calculate report data
            $calculateReportData = function ($orders) {
                $soldQuantity = 0;
                $revenue = 0;
                $invest = 0;
                $profit = 0;


                foreach ($orders as $detail) {
                    $soldQuantity += $detail->quantity;
                    $revenue += $detail->quantity * $detail->product->price_buy;
                    $invest += $detail->quantity * $detail->product->price;
                    $profit += $revenue - $invest;
                }

                $rate = $revenue ? ($profit / $revenue) * 100 : 0;

                return [
                    'soldQuantity' => $soldQuantity,
                    'revenue' => $revenue,
                    'invest' => $invest,
                    'profit' => $profit,
                    'rate' => $rate,
                ];
            };

            switch ($period) {
                case '1': // Today
                    $todayOrders = $this->order->whereIn('id', $orderIds)
                        ->whereDate('created_at', today())
                        ->get();
                    $report[] = $calculateReportData($this->orderDetail->whereIn('order_id', $todayOrders->pluck('id'))->get());
                    break;

                case '2': // This week (from 7 days ago to today)
                    $startOfWeek = now()->startOfWeek();
                    $endOfWeek = now()->endOfWeek();
                    $weeklyOrders = $this->order->whereIn('id', $orderIds)
                        ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                        ->get();
                    $report[] = $calculateReportData($this->orderDetail->whereIn('order_id', $weeklyOrders->pluck('id'))->get());
                    break;

                case '3': // This month (from the 1st to today)
                    $startOfMonth = now()->startOfMonth();
                    $endOfMonth = now();
                    $monthlyOrders = $this->order->whereIn('id', $orderIds)
                        ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                        ->get();
                    $report[] = $calculateReportData($this->orderDetail->whereIn('order_id', $monthlyOrders->pluck('id'))->get());
                    break;

                case '4': // This quarter (from the start of the quarter to today)
                    $startOfQuarter = now()->startOfQuarter();
                    $endOfQuarter = now();
                    $quarterlyOrders = $this->order->whereIn('id', $orderIds)
                        ->whereBetween('created_at', [$startOfQuarter, $endOfQuarter])
                        ->get();
                    $report[] = $calculateReportData($this->orderDetail->whereIn('order_id', $quarterlyOrders->pluck('id'))->get());
                    break;

                case '5': // This year (from the start of the year to today)
                    $startOfYear = now()->startOfYear();
                    $endOfYear = now();
                    $yearlyOrders = $this->order->whereIn('id', $orderIds)
                        ->whereBetween('created_at', [$startOfYear, $endOfYear])
                        ->get();
                    $report[] = $calculateReportData($this->orderDetail->whereIn('order_id', $yearlyOrders->pluck('id'))->get());
                    break;

                case '6': // Custom date range
                    $startDate = request()->input('start_date');
                    $endDate = request()->input('end_date');
                    $customOrders = $this->order->whereIn('id', $orderIds)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->get();
                    $report[] = $calculateReportData($this->orderDetail->whereIn('order_id', $customOrders->pluck('id'))->get());
                    break;

                default:
                    throw new Exception('Invalid period');
            }

            return $report;
        } catch (Exception $e) {
            Log::error('Failed to generate profit report: ' . $e->getMessage());
            return [];
        }
    }
}
