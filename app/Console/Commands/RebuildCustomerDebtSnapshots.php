<?php

namespace App\Console\Commands;

use App\Services\Accounting\CustomerDebtSnapshotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RebuildCustomerDebtSnapshots extends Command
{
    protected $signature = 'accounting:rebuild-customer-debt-snapshots
                            {--from-year= : Năm ledger đầu tiên có thể đã thay đổi}
                            {--owner= : Chỉ rebuild canonical owner này}
                            {--chunk=1000 : Số Client mỗi chunk}';

    protected $description = 'Invalidate và cascade rebuild opening TK131 sau năm ledger đã thay đổi';

    public function handle(CustomerDebtSnapshotService $service): int
    {
        $ledgerYear = (int) $this->option('from-year');

        if ($ledgerYear < 1) {
            $this->error('--from-year là bắt buộc và phải là một năm hợp lệ.');

            return self::INVALID;
        }

        $chunk = max(1, (int) $this->option('chunk'));
        $ownerOption = $this->option('owner');
        $ownerIds = $ownerOption
            ? collect([(int) $ownerOption])
            : DB::table('clients')->whereNotNull('user_id')->distinct()->orderBy('user_id')->pluck('user_id');
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
