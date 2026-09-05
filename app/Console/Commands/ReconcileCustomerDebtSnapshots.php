<?php

namespace App\Console\Commands;

use App\Models\CustomerDebtYearlySnapshot;
use App\Services\Accounting\CustomerDebtSnapshotService;
use App\Support\DecimalAmount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ReconcileCustomerDebtSnapshots extends Command
{
    protected $signature = 'accounting:reconcile-customer-debt-snapshots
                            {--year= : Năm opening cần đối chiếu}
                            {--owner= : Chỉ canonical owner này}
                            {--branch= : Chỉ Branch này; dùng NULL cho legacy}
                            {--client= : Chỉ Client này}';

    protected $description = 'Read-only reconcile snapshot TK131 với full ledger';

    public function handle(CustomerDebtSnapshotService $service): int
    {
        $year = (int) ($this->option('year') ?: now()->year);
        $query = CustomerDebtYearlySnapshot::query()
            ->where('fiscal_year', $year)
            ->when($this->option('owner'), fn ($builder, $owner) => $builder->where('owner_id', (int) $owner))
            ->when($this->option('client'), fn ($builder, $client) => $builder->where('client_id', (int) $client))
            ->when(
                $this->option('branch') !== null && Schema::hasColumn('customer_debt_yearly_snapshots', 'branch_id'),
                fn ($builder) => strtoupper((string) $this->option('branch')) === 'NULL'
                    ? $builder->whereNull('branch_id')
                    : $builder->where('branch_id', (int) $this->option('branch'))
            )
            ->orderBy('owner_id')
            ->orderBy('client_id');
        $checked = 0;
        $mismatches = 0;

        $query->chunkById(1000, function ($snapshots) use ($service, $year, &$checked, &$mismatches): void {
            foreach ($snapshots as $snapshot) {
                $branchScoped = Schema::hasColumn('transactions', 'branch_id')
                    && Schema::hasColumn('customer_debt_yearly_snapshots', 'branch_id');
                $branchId = $branchScoped && $snapshot->branch_id !== null
                    ? (int) $snapshot->branch_id
                    : null;
                $ledgerNet = $service->fullLedgerOpeningNets(
                    collect([(int) $snapshot->client_id]),
                    $year,
                    $branchId,
                    $branchScoped
                )->get((int) $snapshot->client_id, '0.00');
                $snapshotNet = DecimalAmount::subtract(
                    (string) $snapshot->opening_debit,
                    (string) $snapshot->opening_credit
                );
                $difference = DecimalAmount::subtract($snapshotNet, $ledgerNet);
                $checked++;

                if (! DecimalAmount::isZero($difference)) {
                    $mismatches++;
                    $this->error(
                        "owner={$snapshot->owner_id} branch=".($snapshot->branch_id ?? 'NULL')
                        ." client={$snapshot->client_id} "
                        ."snapshot={$snapshotNet} ledger={$ledgerNet} difference={$difference}"
                    );
                }
            }
        });

        $this->info("Checked={$checked}; mismatches={$mismatches}; expected difference=0.00");

        return $mismatches === 0 ? self::SUCCESS : self::FAILURE;
    }
}
