<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class BackfillOrderCustomerSnapshots extends Command
{
    protected $signature = 'orders:backfill-customer-snapshots
                            {--chunk=200 : Số đơn xử lý trong mỗi lượt}';

    protected $description = 'Điền snapshot khách hàng còn thiếu cho các đơn vẫn còn liên kết khách hàng';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $availableColumns = Schema::getColumnListing('orders');
        $snapshotMap = collect([
            'name' => 'name',
            'phone' => 'phone',
            'email' => 'email',
            'receive_address' => 'address',
        ])->filter(
            fn (string $clientAttribute, string $orderAttribute) =>
                in_array($orderAttribute, $availableColumns, true)
        );

        if ($snapshotMap->isEmpty()) {
            $this->warn('Bảng orders không có cột snapshot khách hàng phù hợp.');

            return self::FAILURE;
        }

        $scanned = 0;
        $updated = 0;

        Order::query()
            ->whereNotNull('client_id')
            ->whereHas('client')
            ->where(function ($query) use ($snapshotMap): void {
                foreach ($snapshotMap->keys() as $column) {
                    $query->orWhereNull($column)->orWhere($column, '');
                }
            })
            ->with('client')
            ->chunkById($chunkSize, function ($orders) use (
                $snapshotMap,
                &$scanned,
                &$updated
            ): void {
                foreach ($orders as $order) {
                    $scanned++;
                    $changes = [];

                    foreach ($snapshotMap as $orderAttribute => $clientAttribute) {
                        if (! $this->isBlank($order->getRawOriginal($orderAttribute))) {
                            continue;
                        }

                        $clientValue = $order->client?->{$clientAttribute};

                        if ($this->isBlank($clientValue)) {
                            continue;
                        }

                        $changes[$orderAttribute] = $clientValue;
                    }

                    if ($changes === []) {
                        continue;
                    }

                    $order->forceFill($changes)->saveQuietly();
                    $updated++;
                }
            });

        $this->info("Đã kiểm tra {$scanned} đơn và cập nhật {$updated} đơn.");

        return self::SUCCESS;
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }
}
