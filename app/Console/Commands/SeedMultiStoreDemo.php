<?php

namespace App\Console\Commands;

use Database\Seeders\MultiStoreDemoSeeder;
use Illuminate\Console\Command;
use Throwable;

class SeedMultiStoreDemo extends Command
{
    protected $signature = 'demo:seed-multistore
                            {--reset : Xóa business data local trước khi seed}
                            {--include-legacy : Thêm một số record LEGACY-DEMO branch NULL}';

    protected $description = 'Tạo dataset demo multi-store deterministic cho kiểm thử local/dev';

    public function handle(MultiStoreDemoSeeder $seeder): int
    {
        try {
            $summary = $seeder->run((bool) $this->option('reset'), (bool) $this->option('include-legacy'));
        } catch (Throwable $exception) {
            $this->error('MULTI-STORE DEMO SEED FAILED: '.$exception->getMessage());
            report($exception);

            return self::FAILURE;
        }

        $this->info('MULTI-STORE DEMO DATA READY');
        $this->line('Password for ALL demo accounts: 123456');
        $this->line('Test guide: storage/app/demo/multistore-test-guide.md');
        $this->line('Accounts:');
        foreach ($summary['accounts'] as $account) {
            $this->line('  '.$account['email'].' | role_id='.$account['role_id'].' | branch_id='.($account['branch_id'] ?? 'NULL').' | storage_id='.($account['storage_id'] ?? 'NULL'));
        }
        $this->line('Counts: '.json_encode($summary['counts'], JSON_UNESCAPED_UNICODE));
        $this->line('Dashboard:');
        foreach ($summary['dashboard'] as $scope => $row) {
            $this->line('  '.$scope.': '.json_encode($row, JSON_UNESCAPED_UNICODE));
        }
        $this->line('Snapshot reconcile: '.$summary['snapshot_reconcile']);

        return self::SUCCESS;
    }
}
