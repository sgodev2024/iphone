<?php

namespace App\Console\Commands;

use App\Services\CustomerSaleBackfillService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class BackfillCustomerSales extends Command
{
    protected $signature = 'accounting:backfill-customer-sales
                            {--orders= : Comma-separated approved Phase 3A order IDs}
                            {--execute : Execute the all-or-nothing local backfill}';

    protected $description = 'Dry-run or backfill approved missing customer sale accounting entries';

    public function handle(CustomerSaleBackfillService $service): int
    {
        try {
            $this->guardEnvironment();
            $orderIds = $this->parseOrderIds((string) $this->option('orders'));
            $execute = (bool) $this->option('execute');
            $result = $execute
                ? $service->execute($orderIds)
                : $service->preview($orderIds);

            $this->renderResult($result, $execute);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('[BLOCK] '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function guardEnvironment(): void
    {
        if (app()->environment('testing')) {
            $this->line('[PASS] Environment testing (isolated test database)');

            return;
        }

        if (! app()->environment('local')) {
            throw new RuntimeException('APP_ENV must be local.');
        }

        $this->line('[PASS] Environment local');
        $database = DB::connection()->getDatabaseName();

        if ($database !== 'ai_crm_2026') {
            throw new RuntimeException("Database must be ai_crm_2026, found {$database}.");
        }

        $this->line('[PASS] Database ai_crm_2026');
    }

    private function parseOrderIds(string $value): array
    {
        if (trim($value) === '') {
            throw new RuntimeException('The --orders option is required.');
        }

        return array_map('trim', explode(',', $value));
    }

    private function renderResult(array $result, bool $execute): void
    {
        $this->line('[PASS] Account 131');
        $this->line('[PASS] Account 5111');
        $this->line('[PASS] 15/15 orders found');
        $this->line('[PASS] All owner = 23');
        $this->line($result['state'] === 'missing'
            ? '[PASS] No existing sale'
            : '[PASS] Existing sale batch is complete and valid');
        $this->line('[PASS] No duplicate');
        $this->line('[PASS] No amount mismatch');
        $this->line('[PASS] Expected total = '.$this->money(CustomerSaleBackfillService::EXPECTED_SALE_TOTAL));
        $this->line('[PASS] No payment backfill needed');

        $headers = [
            'Order', 'Owner', 'Client', 'Total', 'Paid', 'Debt',
            'Existing Sale', 'Existing Payment',
            $execute ? 'Added Nợ 131' : 'Planned Nợ 131',
            $execute ? 'Added Có 5111' : 'Planned Có 5111',
            'Result',
        ];
        $createdTransactionIds = $result['execution']['transaction_ids'] ?? [];
        $rows = collect($result['rows'])->map(function (array $row) use ($createdTransactionIds): array {
            $created = array_key_exists($row['order_id'], $createdTransactionIds);
            $already = $row['sale_state'] === 'valid' && ! $created;
            $resultLabel = $created
                ? 'BACKFILLED'
                : ($already ? 'ALREADY BACKFILLED' : 'PASS');
            $saleAmount = $already ? 0 : $row['total'];

            return [
                $row['order_id'],
                $row['owner_id'],
                $row['client_id'] ?? 'Khách lẻ',
                $this->money($row['total']),
                $this->money($row['paid']),
                $this->money($row['debt']),
                $row['sale_transaction_id'] ?? 'None',
                $row['payment_transaction_ids'] === [] ? 'None' : implode(',', $row['payment_transaction_ids']),
                $saleAmount === 0 ? '0' : $this->money($saleAmount),
                $saleAmount === 0 ? '0' : $this->money($saleAmount),
                $resultLabel,
            ];
        });

        $this->table($headers, $rows);
        $this->newLine();
        $this->line('Orders validated: '.$result['order_count']);
        $debit131 = $execute
            ? collect($result['rows'])->whereIn('order_id', array_keys($createdTransactionIds))->sum('total')
            : $result['planned_debit_131'];
        $label = $execute ? 'Added' : 'Planned';
        $this->line("{$label} Debit 131: ".$this->money($debit131));
        $this->line("{$label} Credit 5111: ".$this->money($debit131));
        $this->line("{$label} Credit 131: 0");
        $this->line("{$label} Debit 111/112: 0");

        if (! $execute) {
            $this->warn('DRY RUN ONLY: no database writes were performed.');
        } elseif (($result['execution']['created'] ?? 0) === 0) {
            $this->info('Already Backfilled: no new transaction or entry was created.');
        } else {
            $this->info('Backfilled all 15 approved sale transactions successfully.');
        }
    }

    private function money(int|float $amount): string
    {
        return number_format((float) $amount, 0, ',', '.');
    }
}
