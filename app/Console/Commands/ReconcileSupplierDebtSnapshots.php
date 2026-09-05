<?php

namespace App\Console\Commands;

use App\Models\SupplierDebtYearlySnapshot;
use App\Services\Accounting\SupplierDebtSnapshotService;
use App\Support\DecimalAmount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ReconcileSupplierDebtSnapshots extends Command
{
    protected $signature = 'accounting:reconcile-supplier-debt-snapshots
                            {--year= : Năm opening cần đối chiếu}
                            {--owner= : Chỉ canonical owner này}
                            {--branch= : Chỉ Branch này; dùng NULL cho legacy}
                            {--company= : Chỉ Company này}';

    protected $description = 'Read-only reconcile snapshot TK331 với full ledger';

    public function handle(SupplierDebtSnapshotService $service): int
    {
        $year = (int) ($this->option('year') ?: now()->year);
        if ($year < 1970 || $year > 2100) {
            $this->error('--year phải nằm trong khoảng 1970-2100.');

            return self::INVALID;
        }

        $query = SupplierDebtYearlySnapshot::query()
            ->where('fiscal_year', $year)
            ->when($this->option('owner') !== null, fn ($builder) => $builder->where('owner_id', (int) $this->option('owner')))
            ->when($this->option('company') !== null, fn ($builder) => $builder->where('company_id', (int) $this->option('company')))
            ->when(
                $this->option('branch') !== null && Schema::hasColumn('supplier_debt_yearly_snapshots', 'branch_id'),
                fn ($builder) => strtoupper((string) $this->option('branch')) === 'NULL'
                    ? $builder->whereNull('branch_id')
                    : $builder->where('branch_id', (int) $this->option('branch'))
            )
            ->orderBy('owner_id')
            ->orderBy('company_id');
        $checked = 0;
        $mismatches = 0;

        $query->chunkById(1000, function ($snapshots) use ($service, &$checked, &$mismatches): void {
            foreach ($snapshots as $snapshot) {
                $ledgerNet = $service->fullLedgerOpeningNets(
                    (int) $snapshot->owner_id,
                    collect([(int) $snapshot->company_id]),
                    (int) $snapshot->fiscal_year,
                    $snapshot->branch_id === null ? null : (int) $snapshot->branch_id,
                    Schema::hasColumn('transactions', 'branch_id')
                        && Schema::hasColumn('supplier_debt_yearly_snapshots', 'branch_id')
                )->get((int) $snapshot->company_id, '0.00');
                $snapshotNet = DecimalAmount::subtract(
                    (string) $snapshot->opening_credit,
                    (string) $snapshot->opening_debit
                );
                $difference = DecimalAmount::subtract($snapshotNet, $ledgerNet);
                $checked++;

                if (! DecimalAmount::isZero($difference)) {
                    $mismatches++;
                    $this->error(
                        "owner={$snapshot->owner_id} branch=".($snapshot->branch_id ?? 'NULL')
                        ." company={$snapshot->company_id} "
                        ."snapshot={$snapshotNet} ledger={$ledgerNet} difference={$difference}"
                    );
                }
            }
        });

        $this->info("Checked={$checked}; mismatches={$mismatches}; expected difference=0.00");

        return $mismatches === 0 ? self::SUCCESS : self::FAILURE;
    }
}
