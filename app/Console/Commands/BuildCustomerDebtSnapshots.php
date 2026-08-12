<?php

namespace App\Console\Commands;

use App\Services\Accounting\CustomerDebtSnapshotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class BuildCustomerDebtSnapshots extends Command
{
    protected $signature = 'accounting:build-customer-debt-snapshots
                            {--year= : Năm opening cần build}
                            {--owner= : Chỉ build canonical owner này}
                            {--chunk=1000 : Số Client mỗi chunk}';

    protected $description = 'Build idempotent opening snapshot TK131 theo năm';

    public function handle(CustomerDebtSnapshotService $service): int
    {
        $year = (int) ($this->option('year') ?: now()->year);
        $chunk = max(1, (int) $this->option('chunk'));
        $ownerOption = $this->option('owner');
        $ownerIds = $ownerOption
            ? collect([(int) $ownerOption])
            : DB::table('clients')->whereNotNull('user_id')->distinct()->orderBy('user_id')->pluck('user_id');
        $totals = ['scanned' => 0, 'built' => 0, 'rebuilt' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($ownerIds as $ownerId) {
            try {
                $result = $service->buildOwnerYear((int) $ownerId, $year, $chunk);

                foreach ($totals as $key => $value) {
                    $totals[$key] += $result[$key];
                }
            } catch (Throwable $exception) {
                $totals['failed']++;
                $this->error("Owner {$ownerId}: {$exception->getMessage()}");
            }
        }

        $this->table(array_keys($totals), [array_values($totals)]);

        return $totals['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
