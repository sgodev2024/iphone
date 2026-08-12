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
                            {--orders= : Comma-separated IDs for one explicit approved accounting batch}
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
            $this->line('[PASS] APP_ENV testing (isolated test database)');

            return;
        }

        if (! app()->environment('local')) {
            throw new RuntimeException('APP_ENV must be local.');
        }

        $this->line('[PASS] APP_ENV local');
        $database = DB::connection()->getDatabaseName();

        if ($database !== 'ai_crm_2026') {
            throw new RuntimeException("Database must be ai_crm_2026, found {$database}.");
        }

        $this->line('[PASS] DB ai_crm_2026');
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
        $batch = $result['batch'];
        $orderCount = count($batch['orders']);
        $clientIds = collect($batch['orders'])->pluck('client_id')->filter()->unique()->sort()->values()->implode('/');
        $this->line('[PASS] Approved batch '.$batch['label']);
        $this->line('[PASS] Exact approved whitelist');
        $this->line('[PASS] Account 131 active');
        $this->line('[PASS] Account 5111 active');
        $this->line("[PASS] {$orderCount}/{$orderCount} orders found");
        $this->line('[PASS] All owner = '.$batch['owner_id']);
        $this->line('[PASS] Clients exactly '.($clientIds !== '' ? $clientIds : 'customerless as approved'));
        $this->line($result['state'] === 'missing'
            ? '[PASS] No existing sale'
            : '[PASS] Existing sale batch is complete and valid');
        $this->line('[PASS] Existing payment = '.$this->money($result['payment_metrics']['credit_131']));
        $this->line('[PASS] No payment duplicate');
        $this->line('[PASS] No Client 8');
        $this->line('[PASS] No amount mismatch');
        $this->line('[PASS] Expected total = '.$this->money($batch['sale_total']));

        $headers = [
            'Order', 'Owner', 'Client', 'Total', 'Payment Evidence', 'Existing Sale',
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
                $row['payment_evidence'],
                $row['sale_transaction_id'] ?? 'None',
                $saleAmount === 0 ? '0' : $this->money($saleAmount),
                $saleAmount === 0 ? '0' : $this->money($saleAmount),
                $resultLabel,
            ];
        });

        $this->table($headers, $rows);
        $this->newLine();
        $this->line('Orders validated: '.$result['order_count']);
        $this->line('Total: '.$this->money($result['sale_total']));
        $debit131 = $execute
            ? collect($result['rows'])->whereIn('order_id', array_keys($createdTransactionIds))->sum('total')
            : $result['planned_debit_131'];
        $label = $execute ? 'Added' : 'Planned';
        $this->line("{$label} Debit 131: ".$this->money($debit131));
        $this->line("{$label} Credit 5111: ".$this->money($debit131));
        $this->line("{$label} Credit 131: 0");
        $this->line("{$label} Debit 111: 0");
        $this->line("{$label} Debit 112: 0");

        if (! $execute) {
            $this->warn('DRY RUN ONLY: no database writes were performed.');
        } elseif (($result['execution']['created'] ?? 0) === 0) {
            $this->info('Already Backfilled: no new transaction or entry was created.');
        } else {
            $this->info("Backfilled all {$orderCount} approved sale transactions successfully.");
        }
    }

    private function money(int|float $amount): string
    {
        return number_format((float) $amount, 0, ',', '.');
    }
}
