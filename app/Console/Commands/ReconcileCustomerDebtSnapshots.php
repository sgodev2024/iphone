<?php

namespace App\Console\Commands;

use App\Models\CustomerDebtYearlySnapshot;
use App\Services\Accounting\CustomerDebtSnapshotService;
use App\Support\DecimalAmount;
use Illuminate\Console\Command;

class ReconcileCustomerDebtSnapshots extends Command
{
    protected $signature = 'accounting:reconcile-customer-debt-snapshots
                            {--year= : Năm opening cần đối chiếu}
                            {--owner= : Chỉ canonical owner này}
                            {--client= : Chỉ Client này}';

    protected $description = 'Read-only reconcile snapshot TK131 với full ledger';

    public function handle(CustomerDebtSnapshotService $service): int
    {
        $year = (int) ($this->option('year') ?: now()->year);
        $query = CustomerDebtYearlySnapshot::query()
            ->where('fiscal_year', $year)
            ->when($this->option('owner'), fn ($builder, $owner) => $builder->where('owner_id', (int) $owner))
            ->when($this->option('client'), fn ($builder, $client) => $builder->where('client_id', (int) $client))
            ->orderBy('owner_id')
            ->orderBy('client_id');
        $checked = 0;
        $mismatches = 0;

        $query->chunkById(1000, function ($snapshots) use ($service, $year, &$checked, &$mismatches): void {
            $ledgerNets = $service->fullLedgerOpeningNets(
                $snapshots->pluck('client_id')->map(fn ($id) => (int) $id),
                $year
            );

            foreach ($snapshots as $snapshot) {
                $snapshotNet = DecimalAmount::subtract(
                    (string) $snapshot->opening_debit,
                    (string) $snapshot->opening_credit
                );
                $ledgerNet = $ledgerNets->get((int) $snapshot->client_id, '0.00');
                $difference = DecimalAmount::subtract($snapshotNet, $ledgerNet);
                $checked++;

                if (! DecimalAmount::isZero($difference)) {
                    $mismatches++;
                    $this->error(
                        "owner={$snapshot->owner_id} client={$snapshot->client_id} "
                        ."snapshot={$snapshotNet} ledger={$ledgerNet} difference={$difference}"
                    );
                }
            }
        });

        $this->info("Checked={$checked}; mismatches={$mismatches}; expected difference=0.00");

        return $mismatches === 0 ? self::SUCCESS : self::FAILURE;
    }
}
