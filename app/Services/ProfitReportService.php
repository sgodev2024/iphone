<?php

namespace App\Services;

use App\Models\OrderDetail;
use App\Models\OrderReturnDetail;
use App\Models\Storage;
use App\Models\User;
use App\Support\BranchContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProfitReportService
{
    public function __construct(private readonly BranchContext $branchContext)
    {
    }

    public function storage(User $actor, int $storageId): Storage
    {
        return $this->branchContext
            ->scopeStorages(Storage::query(), $actor)
            ->findOrFail($storageId);
    }

    public function report(
        User $actor,
        ?Storage $storage,
        string $filter,
        ?string $startDate = null,
        ?string $endDate = null,
        string $search = ''
    ): array {
        $period = $this->period($filter, $startDate, $endDate);
        $sales = $this->sales($actor, $storage, $period, $search)->get();
        $returns = $this->returns($actor, $storage, $period, $search)->get();

        return $this->aggregate($sales, $returns);
    }

    private function period(string $filter, ?string $startDate, ?string $endDate): ?array
    {
        $now = Carbon::now();

        return match ($filter) {
            'all' => null,
            '1' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '2' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            '3' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            '4' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            '5' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            '6' => [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()],
        };
    }

    private function sales(User $actor, ?Storage $storage, ?array $period, string $search)
    {
        return OrderDetail::query()
            ->join('orders as report_orders', 'report_orders.id', '=', 'order_details.order_id')
            ->join('storages as report_storages', 'report_storages.id', '=', 'order_details.storage_id')
            ->join('products as report_products', 'report_products.id', '=', 'order_details.product_id')
            ->whereColumn('report_orders.branch_id', 'report_storages.branch_id')
            ->whereColumn('report_products.branch_id', 'report_orders.branch_id')
            ->select('order_details.*')
            ->whereHas('order', function ($query) use ($actor, $storage, $period): void {
                $query->where('status', 1);
                $this->branchContext->scope($query, $actor);
                if ($storage) {
                    $query->where('branch_id', $storage->branch_id);
                }
                if ($period) {
                    $query->whereBetween('created_at', $period);
                }
            })
            ->when($storage, fn ($query) => $query->where('storage_id', $storage->id))
            ->when($search !== '', fn ($query) => $query->whereHas('product', fn ($products) =>
                $products->where(function ($match) use ($search): void {
                    $match->where('code', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%');
                })
            ))
            ->with(['product', 'productImei.importDetail', 'order.orderDetails']);
    }

    private function returns(User $actor, ?Storage $storage, ?array $period, string $search)
    {
        // Both foreign keys and matching snapshots must point to the original sold line.
        $query = OrderReturnDetail::query()
            ->join('order_returns', 'order_returns.id', '=', 'order_return_details.order_return_id')
            ->join('order_details as original_details', 'original_details.id', '=', 'order_return_details.order_detail_id')
            ->join('orders as original_orders', 'original_orders.id', '=', 'original_details.order_id')
            ->join('storages as original_storages', 'original_storages.id', '=', 'original_details.storage_id')
            ->join('products as original_products', 'original_products.id', '=', 'original_details.product_id')
            ->whereColumn('original_storages.branch_id', 'original_orders.branch_id')
            ->whereColumn('original_products.branch_id', 'original_orders.branch_id')
            ->whereColumn('order_returns.original_order_id', 'original_details.order_id')
            ->whereColumn('order_returns.branch_id', 'original_orders.branch_id')
            ->whereColumn('order_return_details.storage_id', 'original_details.storage_id')
            ->whereColumn('order_return_details.product_id', 'original_details.product_id')
            ->where('order_returns.status', 'completed')
            ->where('original_orders.status', 1)
            ->select('order_return_details.*')
            ->with(['product', 'orderDetail.product', 'orderDetail.productImei.importDetail']);

        if (! $this->branchContext->isGlobal($actor)) {
            $query->where('original_orders.branch_id', $this->branchContext->branchId($actor));
        }
        if ($storage) {
            $query->where('order_return_details.storage_id', $storage->id)
                ->where('original_orders.branch_id', $storage->branch_id);
        }
        if ($period) {
            // A return affects the period in which the completed return was created.
            $query->whereBetween('order_returns.created_at', $period);
        }
        if ($search !== '') {
            $query->whereHas('product', fn ($products) => $products->where(function ($match) use ($search): void {
                $match->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            }));
        }

        return $query;
    }

    private function aggregate(Collection $sales, Collection $returns): array
    {
        $rows = [];
        foreach ($sales as $detail) {
            $product = $detail->getRelation('product');
            if (! $product) {
                continue;
            }
            $id = (int) $detail->product_id;
            $rows[$id] ??= ['product' => $product, 'quantity' => 0, 'revenue' => 0.0, 'cost' => 0.0, 'legacy_cost' => false];
            $quantity = (int) $detail->quantity;
            $gross = (float) $detail->price * $quantity;
            $subtotal = (float) $detail->order?->orderDetails->sum(
                fn (OrderDetail $line) => (float) $line->price * (int) $line->quantity
            );
            $rows[$id]['quantity'] += $quantity;
            $rows[$id]['revenue'] += $subtotal > 0 ? (float) $detail->order->total_money * $gross / $subtotal : 0;
            $rows[$id]['cost'] += $this->hasSnapshot($detail)
                ? (float) $detail->cost_total_snapshot
                : $this->unitCost($detail) * $quantity;
            $rows[$id]['legacy_cost'] = $rows[$id]['legacy_cost'] || ! $this->hasSnapshot($detail);
        }

        foreach ($returns as $return) {
            $product = $return->getRelation('product');
            $originalDetail = $return->getRelation('orderDetail');
            if (! $product || ! $originalDetail) {
                continue;
            }
            $id = (int) $return->product_id;
            $rows[$id] ??= ['product' => $product, 'quantity' => 0, 'revenue' => 0.0, 'cost' => 0.0, 'legacy_cost' => false];
            $quantity = (int) $return->quantity;
            $rows[$id]['quantity'] -= $quantity;
            $rows[$id]['revenue'] -= (float) $return->return_amount;
            $rows[$id]['cost'] -= $this->unitCost($originalDetail) * $quantity;
            $rows[$id]['legacy_cost'] = $rows[$id]['legacy_cost'] || ! $this->hasSnapshot($originalDetail);
        }

        return collect($rows)
            ->filter(fn (array $row) => $row['quantity'] !== 0 || abs($row['revenue']) > 0.001 || abs($row['cost']) > 0.001)
            ->map(function (array $row): array {
                $row['profit'] = $row['revenue'] - $row['cost'];
                $row['rate'] = $row['revenue'] > 0 ? $row['profit'] / $row['revenue'] * 100 : 0;

                return $row;
            })->values()->all();
    }

    private function hasSnapshot(OrderDetail $detail): bool
    {
        if (($detail->cost_unit_snapshot === null) !== ($detail->cost_total_snapshot === null)) {
            throw new \RuntimeException("Incomplete cost snapshot on order detail {$detail->id}.");
        }

        return $detail->cost_unit_snapshot !== null;
    }

    private function unitCost(OrderDetail $detail): float
    {
        if ($this->hasSnapshot($detail)) {
            return (float) $detail->cost_unit_snapshot;
        }

        // Legacy sale lines have no historical snapshot.
        return (float) ($detail->productImei?->importDetail?->price
            ?? $detail->getRelation('product')?->price_buy
            ?? 0);
    }
}