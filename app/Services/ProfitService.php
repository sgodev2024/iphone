<?php

namespace App\Services;

use App\Models\ImportCoupon;
use App\Models\ImportDetail;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductStorage;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class ProfitService
{
    public function __construct(
        protected Order $order,
        protected OrderDetail $orderDetail,
        protected ImportCoupon $importCoupon,
        protected ImportDetail $importDetail,
        protected Product $product,
        protected ProductStorage $productStorage
    ) {}

    public function profitReport(
        $period,
        $storageId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        try {
            $details = $this->orderDetail
                ->newQuery()
                ->where('storage_id', $storageId)
                ->whereHas('order', function (Builder $query) use ($period, $startDate, $endDate): void {
                    $query->where('status', 1);
                    $this->applyPeriod($query, (string) $period, $startDate, $endDate);
                })
                ->with([
                    'product',
                    'productImei.importDetail',
                    'order.orderDetails',
                ])
                ->get();

            $soldQuantity = 0;
            $netRevenue = 0.0;
            $costOfGoodsSold = 0.0;

            foreach ($details as $detail) {
                $quantity = (int) $detail->quantity;
                $lineRevenue = (float) $detail->price * $quantity;
                $orderSubtotal = (float) $detail->order?->orderDetails->sum(
                    fn (OrderDetail $orderDetail) => (float) $orderDetail->price * (int) $orderDetail->quantity
                );

                $soldQuantity += $quantity;
                $netRevenue += $orderSubtotal > 0
                    ? (float) $detail->order->total_money * ($lineRevenue / $orderSubtotal)
                    : 0;
                $costOfGoodsSold += $this->unitCost($detail) * $quantity;
            }

            $profit = $netRevenue - $costOfGoodsSold;

            return [[
                'soldQuantity' => $soldQuantity,
                'revenue' => $netRevenue,
                'invest' => $costOfGoodsSold,
                'profit' => $profit,
                'rate' => $netRevenue > 0 ? ($profit / $netRevenue) * 100 : 0,
            ]];
        } catch (Exception $e) {
            Log::error('Failed to generate profit report: '.$e->getMessage());

            return [];
        }
    }

    private function applyPeriod(
        Builder $query,
        string $period,
        ?string $startDate,
        ?string $endDate
    ): void {
        match ($period) {
            '1' => $query->whereDate('created_at', today()),
            '2' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            '3' => $query->whereBetween('created_at', [now()->startOfMonth(), now()]),
            '4' => $query->whereBetween('created_at', [now()->startOfQuarter(), now()]),
            '5' => $query->whereBetween('created_at', [now()->startOfYear(), now()]),
            '6' => $query->whereBetween('created_at', [
                Carbon::parse($startDate ?? request()->input('start_date'))->startOfDay(),
                Carbon::parse($endDate ?? request()->input('end_date'))->endOfDay(),
            ]),
            default => throw new Exception('Invalid period'),
        };
    }

    private function unitCost(OrderDetail $detail): float
    {
        $imeiImportCost = $detail->productImei?->importDetail?->price;

        if ($imeiImportCost !== null) {
            return (float) $imeiImportCost;
        }

        return (float) ($detail->product?->price_buy ?? 0);
    }
}
