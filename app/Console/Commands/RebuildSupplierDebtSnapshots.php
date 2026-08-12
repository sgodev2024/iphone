<?php

namespace App\Console\Commands;

use App\Services\Accounting\SupplierDebtSnapshotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RebuildSupplierDebtSnapshots extends Command
{
    protected $signature = 'accounting:rebuild-supplier-debt-snapshots
                            {--from-year= : Năm ledger đầu tiên có thể đã thay đổi}
                            {--owner= : Chỉ rebuild canonical owner này}
                            {--chunk=1000 : Số Company mỗi chunk}';

    protected $description = 'Cascade rebuild opening TK331 sau năm ledger đã thay đổi';

    public function handle(SupplierDebtSnapshotService $service): int
    {
        $ledgerYear = (int) $this->option('from-year');
        if ($ledgerYear < 1970 || $ledgerYear > 2099) {
            $this->error('--from-year là bắt buộc và phải nằm trong khoảng 1970-2099.');

            return self::INVALID;
        }

        $chunk = max(1, (int) $this->option('chunk'));
        $ownerOption = $this->option('owner');
        $ownerIds = $ownerOption !== null
            ? collect([(int) $ownerOption])
            : DB::table('companies')->whereNotNull('user_id')->distinct()->orderBy('user_id')->pluck('user_id');
        $failed = 0;

        $this->info("Ledger từ năm {$ledgerYear} được xem lại; rebuild openings từ ".($ledgerYear + 1).' trở đi.');

        foreach ($ownerIds as $ownerId) {
            try {
                $result = $service->rebuildOwnerFromLedgerYear((int) $ownerId, $ledgerYear, $chunk);
                $this->line("Owner {$ownerId}: ".json_encode($result, JSON_UNESCAPED_UNICODE));
            } catch (Throwable $exception) {
                $failed++;
                $this->error("Owner {$ownerId}: {$exception->getMessage()}");
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
