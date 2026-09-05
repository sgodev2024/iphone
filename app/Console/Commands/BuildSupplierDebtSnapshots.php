<?php

namespace App\Console\Commands;

use App\Services\Accounting\SupplierDebtSnapshotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class BuildSupplierDebtSnapshots extends Command
{
    protected $signature = 'accounting:build-supplier-debt-snapshots
                            {--year= : Năm opening cần build}
                            {--owner= : Chỉ build canonical owner này}
                            {--branch= : Chỉ build Branch này}
                            {--chunk=1000 : Số Company mỗi chunk}';

    protected $description = 'Build idempotent opening snapshot TK331 theo năm';

    public function handle(SupplierDebtSnapshotService $service): int
    {
        $year = (int) ($this->option('year') ?: now()->year);
        if ($year < 1970 || $year > 2100) {
            $this->error('--year phải nằm trong khoảng 1970-2100.');

            return self::INVALID;
        }

        $chunk = max(1, (int) $this->option('chunk'));
        $ownerOption = $this->option('owner');
        $ownerIds = $ownerOption !== null
            ? collect([(int) $ownerOption])
            : DB::table('companies')->whereNotNull('user_id')->distinct()->orderBy('user_id')->pluck('user_id');
        $totals = ['scanned' => 0, 'built' => 0, 'rebuilt' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($ownerIds as $ownerId) {
            $branchIds = $this->option('branch') !== null
                ? collect([(int) $this->option('branch')])
                : collect([null])->merge(
                    DB::table('companies')
                        ->where('user_id', $ownerId)
                        ->whereNotNull('branch_id')
                        ->distinct()
                        ->orderBy('branch_id')
                        ->pluck('branch_id')
                );
            foreach ($branchIds as $branchId) {
                try {
                    $result = $service->buildOwnerYear(
                        (int) $ownerId,
                        $year,
                        $chunk,
                        null,
                        $branchId === null ? null : (int) $branchId,
                        true
                    );

                    foreach ($totals as $key => $value) {
                        $totals[$key] += $result[$key];
                    }
                } catch (Throwable $exception) {
                    $totals['failed']++;
                    $this->error('Owner '.$ownerId.', Branch '.($branchId ?? 'NULL').': '.$exception->getMessage());
                }
            }
        }

        $this->table(array_keys($totals), [array_values($totals)]);

        return $totals['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
