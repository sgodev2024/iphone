<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

class BackfillLegacyCostSnapshots extends Command
{
    protected $signature = 'profit:backfill-cost-snapshots
                            {--dry-run : Validate legacy rows without updating them}';

    protected $description = 'Lock in cost snapshots for legacy order details';

    public function handle(): int
    {
        if (! Schema::hasColumns('order_details', [
            'cost_unit_snapshot',
            'cost_total_snapshot',
            'cost_snapshot_source',
        ])) {
            $this->error('Missing cost snapshot columns. Run migrations first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        try {
            $counts = DB::transaction(function () use ($dryRun): array {
                $rows = $this->legacyRows();
                $updates = [];
                $counts = ['total' => $rows->count(), 'imei' => 0, 'product' => 0, 'partial' => 0];

                foreach ($rows as $row) {
                    if ($row->matched_product_id === null) {
                        throw new RuntimeException("Order detail {$row->id}: product {$row->product_id} is missing.");
                    }

                    $quantity = (int) $row->quantity;
                    if ($quantity < 1) {
                        throw new RuntimeException("Order detail {$row->id}: quantity must be positive.");
                    }

                    if ($row->cost_unit_snapshot !== null) {
                        $unitCents = $this->moneyCents($row->cost_unit_snapshot, (int) $row->id);
                        $counts['partial']++;
                    } elseif ($row->cost_total_snapshot !== null) {
                        $totalCents = $this->moneyCents($row->cost_total_snapshot, (int) $row->id);
                        if ($totalCents % $quantity !== 0) {
                            throw new RuntimeException("Order detail {$row->id}: existing total cost cannot be divided by quantity.");
                        }
                        $unitCents = intdiv($totalCents, $quantity);
                        $counts['partial']++;
                    } else {
                        $importCost = $this->resolvedImeiCost($row);
                        if ($importCost !== null) {
                            $unitCents = $this->moneyCents($importCost, (int) $row->id);
                            $counts['imei']++;
                        } else {
                            $unitCents = $this->moneyCents($row->price_buy, (int) $row->id);
                            $counts['product']++;
                        }
                    }

                    if ($unitCents > intdiv(PHP_INT_MAX, $quantity)) {
                        throw new RuntimeException("Order detail {$row->id}: total cost exceeds supported money range.");
                    }
                    $calculatedTotal = $unitCents * $quantity;
                    if ($row->cost_total_snapshot !== null
                        && $this->moneyCents($row->cost_total_snapshot, (int) $row->id) !== $calculatedTotal
                    ) {
                        throw new RuntimeException("Order detail {$row->id}: existing cost snapshot is inconsistent.");
                    }

                    $updates[] = [
                        'id' => (int) $row->id,
                        'unit' => $this->formatCents($unitCents),
                        'total' => $this->formatCents($calculatedTotal),
                        'source' => $row->cost_snapshot_source ?? 'backfill',
                    ];
                }

                if (! $dryRun) {
                    foreach ($updates as $update) {
                        $affected = DB::table('order_details')
                            ->where('id', $update['id'])
                            ->where(function ($query): void {
                                $query->whereNull('cost_unit_snapshot')
                                    ->orWhereNull('cost_total_snapshot');
                            })
                            ->update([
                                'cost_unit_snapshot' => $update['unit'],
                                'cost_total_snapshot' => $update['total'],
                                'cost_snapshot_source' => $update['source'],
                            ]);

                        if ($affected !== 1) {
                            throw new RuntimeException("Order detail {$update['id']}: concurrent snapshot update detected.");
                        }
                    }
                }

                return $counts;
            }, 3);
        } catch (Throwable $exception) {
            $this->error('Cost snapshot backfill aborted; no rows were updated. '.$exception->getMessage());

            return self::FAILURE;
        }

        $verb = $dryRun ? 'Validated' : 'Updated';
        $this->info(sprintf(
            '%s %d order details (IMEI import: %d, current product cost: %d, partial snapshot: %d).',
            $verb,
            $counts['total'],
            $counts['imei'],
            $counts['product'],
            $counts['partial']
        ));

        return self::SUCCESS;
    }

    private function legacyRows(): Collection
    {
        return DB::table('order_details as detail')
            ->leftJoin('products as product', 'product.id', '=', 'detail.product_id')
            ->leftJoin('product_imeis as imei', 'imei.id', '=', 'detail.product_imei_id')
            ->leftJoin('import_detail as imported', 'imported.id', '=', 'imei.import_detail_id')
            ->leftJoin('import_coupon as coupon', 'coupon.id', '=', 'imported.import_id')
            ->leftJoin('storages as import_storage', 'import_storage.id', '=', 'coupon.storage_id')
            ->leftJoin('orders as sale_order', 'sale_order.id', '=', 'detail.order_id')
            ->select([
                'detail.id',
                'detail.order_id',
                'detail.product_id',
                'detail.product_imei_id',
                'detail.quantity',
                'detail.cost_unit_snapshot',
                'detail.cost_total_snapshot',
                'detail.cost_snapshot_source',
                'product.id as matched_product_id',
                'product.price_buy',
                'imei.product_id as imei_product_id',
                'imported.product_id as imported_product_id',
                'imported.price as import_price',
                'sale_order.branch_id as sale_branch_id',
                'import_storage.branch_id as import_branch_id',
            ])
            ->where(function ($query): void {
                $query->whereNull('detail.cost_unit_snapshot')
                    ->orWhereNull('detail.cost_total_snapshot');
            })
            ->orderBy('detail.id')
            ->lockForUpdate()
            ->get();
    }

    private function resolvedImeiCost(object $row): mixed
    {
        if ($row->product_imei_id === null
            || $row->imei_product_id === null
            || $row->imported_product_id === null
            || (int) $row->imei_product_id !== (int) $row->product_id
            || (int) $row->imported_product_id !== (int) $row->product_id
            || $row->import_price === null
        ) {
            return null;
        }

        if ($row->sale_branch_id !== null
            && $row->import_branch_id !== null
            && (int) $row->sale_branch_id !== (int) $row->import_branch_id
        ) {
            return null;
        }

        return $row->import_price;
    }

    private function moneyCents(mixed $value, int $detailId): int
    {
        if ($value === null || ! preg_match('/^\d+(?:\.\d{1,2})?$/', (string) $value)) {
            throw new RuntimeException("Order detail {$detailId}: cost is missing or invalid.");
        }

        [$whole, $fraction] = array_pad(explode('.', (string) $value, 2), 2, '');
        $whole = ltrim($whole, '0') ?: '0';
        $wholeNumber = filter_var($whole, FILTER_VALIDATE_INT);
        $fractionNumber = (int) str_pad($fraction, 2, '0');
        if ($wholeNumber === false || $wholeNumber > intdiv(PHP_INT_MAX - $fractionNumber, 100)) {
            throw new RuntimeException("Order detail {$detailId}: cost exceeds supported money range.");
        }

        return $wholeNumber * 100 + $fractionNumber;
    }

    private function formatCents(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
