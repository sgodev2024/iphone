<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\SupplierDebtSnapshotState;
use App\Models\SupplierDebtYearlySnapshot;
use App\Support\DecimalAmount;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SupplierDebtSnapshotService
{
    public function reportOpenings(
        int $ownerId,
        int $fiscalYear,
        int $chunkSize = 1000,
        ?string $nameFilter = null,
        ?int $branchId = null,
        bool $branchScoped = false
    ): Collection {
        $this->validateFiscalYear($fiscalYear);
        $this->buildOwnerYear($ownerId, $fiscalYear, $chunkSize, $nameFilter, $branchId, $branchScoped);

        return DB::table('companies as c')
            ->join('supplier_debt_yearly_snapshots as s', function ($join) use ($ownerId, $fiscalYear, $branchId, $branchScoped): void {
                $join->on('s.company_id', '=', 'c.id')
                    ->where('s.owner_id', $ownerId)
                    ->where('s.fiscal_year', $fiscalYear);
                if ($branchScoped) {
                    $join->where('s.branch_id', $branchId);
                }
            })
            ->where('c.user_id', $ownerId)
            ->when($branchScoped, fn ($query) => $query->where('c.branch_id', $branchId))
            ->when($nameFilter !== null && trim($nameFilter) !== '', function ($query) use ($nameFilter): void {
                $query->where('c.name', 'like', '%'.trim($nameFilter).'%');
            })
            ->orderBy('c.id')
            ->get([
                'c.id as company_id',
                's.opening_debit',
                's.opening_credit',
                's.source_through_date',
                's.source_version',
            ])
            ->map(fn ($row): object => (object) [
                'company_id' => (int) $row->company_id,
                'opening_debit' => DecimalAmount::normalize((string) $row->opening_debit),
                'opening_credit' => DecimalAmount::normalize((string) $row->opening_credit),
                'source_through_date' => (string) $row->source_through_date,
                'source_version' => (int) $row->source_version,
            ])
            ->keyBy('company_id');
    }

    public function getOrBuild(int $ownerId, int $companyId, int $fiscalYear): SupplierDebtYearlySnapshot
    {
        $this->validateFiscalYear($fiscalYear);
        $company = Company::query()
            ->whereKey($companyId)
            ->where('user_id', $ownerId)
            ->firstOrFail();

        $branchScoped = Schema::hasColumn('transactions', 'branch_id')
            && Schema::hasColumn('supplier_debt_yearly_snapshots', 'branch_id');
        $branchId = $branchScoped ? ($company->branch_id === null ? null : (int) $company->branch_id) : null;

        $this->buildCompanyChunk(
            $ownerId,
            collect([(int) $company->id]),
            $fiscalYear,
            null,
            $branchId,
            $branchScoped
        );

        return SupplierDebtYearlySnapshot::query()
            ->where('owner_id', $ownerId)
            ->where('company_id', $companyId)
            ->where('fiscal_year', $fiscalYear)
            ->when($branchScoped, fn ($query) => $query->where('branch_id', $branchId))
            ->firstOrFail();
    }

    public function buildOwnerYear(
        int $ownerId,
        int $fiscalYear,
        int $chunkSize = 1000,
        ?string $nameFilter = null,
        ?int $branchId = null,
        bool $branchScoped = false
    ): array {
        $this->validateFiscalYear($fiscalYear);
        $accountId = $this->resolvePayableAccountId();
        $statistics = [
            'scanned' => 0,
            'built' => 0,
            'rebuilt' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        DB::table('companies')
            ->where('user_id', $ownerId)
            ->when($branchScoped, fn ($query) => $query->where('branch_id', $branchId))
            ->when($nameFilter !== null && trim($nameFilter) !== '', function ($query) use ($nameFilter): void {
                $query->where('name', 'like', '%'.trim($nameFilter).'%');
            })
            ->orderBy('id')
            ->chunkById(max(1, $chunkSize), function (Collection $companies) use (
                $ownerId,
                $fiscalYear,
                $accountId,
                $branchId,
                $branchScoped,
                &$statistics
            ): void {
                $companyIds = $companies->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
                $chunkStatistics = $this->buildCompanyChunk(
                    $ownerId,
                    $companyIds,
                    $fiscalYear,
                    $accountId,
                    $branchId,
                    $branchScoped
                );

                foreach ($statistics as $key => $value) {
                    $statistics[$key] += $chunkStatistics[$key];
                }
            }, 'id');

        return $statistics;
    }

    public function rebuildOwnerFromLedgerYear(
        int $ownerId,
        int $ledgerYear,
        int $chunkSize = 1000,
        ?int $branchId = null,
        bool $branchScoped = false
    ): array {
        $this->validateFiscalYear($ledgerYear);
        $openingYear = $ledgerYear + 1;
        $targetYear = max(
            now()->year,
            $openingYear,
            (int) SupplierDebtYearlySnapshot::query()
                ->where('owner_id', $ownerId)
                ->when($branchScoped, fn ($query) => $query->where('branch_id', $branchId))
                ->max('fiscal_year')
        );
        $now = now();

        DB::table('companies')
            ->where('user_id', $ownerId)
            ->when($branchScoped, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('id')
            ->chunkById(max(1, $chunkSize), function (Collection $companies) use (
                $ownerId,
                $openingYear,
                $now,
                $branchId,
                $branchScoped
            ): void {
                $companyIds = $companies->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
                $rows = $companyIds->map(fn (int $companyId): array => [
                    'owner_id' => $ownerId,
                    ...$this->branchAttribute('supplier_debt_snapshot_states', $branchId),
                    'company_id' => $companyId,
                    'ledger_version' => 0,
                    'dirty_from_year' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::transaction(function () use (
                    $rows,
                    $ownerId,
                    $companyIds,
                    $openingYear,
                    $branchId,
                    $branchScoped
                ): void {
                    foreach ($rows as $row) {
                        $stateQuery = DB::table('supplier_debt_snapshot_states')->where([
                            'owner_id' => $row['owner_id'],
                            'company_id' => $row['company_id'],
                            ...$this->branchAttribute('supplier_debt_snapshot_states', $branchId),
                        ]);
                        if (! $stateQuery->exists()) {
                            DB::table('supplier_debt_snapshot_states')->insert($row);
                        }
                    }
                    $states = SupplierDebtSnapshotState::query()
                        ->where('owner_id', $ownerId)
                        ->when($branchScoped, fn ($query) => $query->where('branch_id', $branchId))
                        ->whereIn('company_id', $companyIds)
                        ->orderBy('company_id')
                        ->lockForUpdate()
                        ->get();

                    foreach ($states as $state) {
                        $state->forceFill([
                            'ledger_version' => (int) $state->ledger_version + 1,
                            'dirty_from_year' => $state->dirty_from_year === null
                                ? $openingYear
                                : min((int) $state->dirty_from_year, $openingYear),
                        ])->save();
                    }
                }, 3);
            }, 'id');

        return $this->buildOwnerYear(
            $ownerId,
            $targetYear,
            $chunkSize,
            null,
            $branchId,
            $branchScoped
        );
    }

    public function fullLedgerOpeningNets(
        int $ownerId,
        Collection $companyIds,
        int $fiscalYear,
        ?int $branchId = null,
        bool $branchScoped = false
    ): Collection {
        return $this->aggregateTotals(
            $ownerId,
            $companyIds,
            null,
            Carbon::create($fiscalYear, 1, 1)->toDateString(),
            null,
            $branchId,
            $branchScoped
        )->map(fn (array $totals): string => DecimalAmount::subtract(
            $totals['credit'],
            $totals['debit']
        ));
    }

    public function reconcileSnapshot(SupplierDebtYearlySnapshot $snapshot): string
    {
        $ledgerNet = $this->fullLedgerOpeningNets(
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

        return DecimalAmount::subtract($snapshotNet, $ledgerNet);
    }

    private function buildCompanyChunk(
        int $ownerId,
        Collection $companyIds,
        int $fiscalYear,
        ?int $accountId = null,
        ?int $branchId = null,
        bool $branchScoped = false
    ): array {
        $statistics = [
            'scanned' => $companyIds->count(),
            'built' => 0,
            'rebuilt' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        if ($companyIds->isEmpty()) {
            return $statistics;
        }

        $accountId ??= $this->resolvePayableAccountId();

        return DB::transaction(function () use (
            $ownerId,
            $companyIds,
            $fiscalYear,
            $accountId,
            $branchId,
            $branchScoped,
            $statistics
        ): array {
            $now = now();
            $stateRows = $companyIds->map(fn (int $companyId): array => [
                'owner_id' => $ownerId,
                ...$this->branchAttribute('supplier_debt_snapshot_states', $branchId),
                'company_id' => $companyId,
                'ledger_version' => 0,
                'dirty_from_year' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();
            foreach ($stateRows as $row) {
                $stateQuery = DB::table('supplier_debt_snapshot_states')->where([
                    'owner_id' => $row['owner_id'],
                    'company_id' => $row['company_id'],
                    ...$this->branchAttribute('supplier_debt_snapshot_states', $branchId),
                ]);
                if (! $stateQuery->exists()) {
                    DB::table('supplier_debt_snapshot_states')->insert($row);
                }
            }

            $states = SupplierDebtSnapshotState::query()
                ->where('owner_id', $ownerId)
                ->when($branchScoped, fn ($query) => $query->where('branch_id', $branchId))
                ->whereIn('company_id', $companyIds)
                ->orderBy('company_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('company_id');

            if ($states->count() !== $companyIds->count()) {
                throw new RuntimeException('Không thể khóa đầy đủ trạng thái snapshot công nợ nhà cung cấp.');
            }

            $snapshots = SupplierDebtYearlySnapshot::query()
                ->where('owner_id', $ownerId)
                ->when($branchScoped, fn ($query) => $query->where('branch_id', $branchId))
                ->whereIn('company_id', $companyIds)
                ->orderBy('company_id')
                ->orderBy('fiscal_year')
                ->get()
                ->groupBy('company_id')
                ->map(fn (EloquentCollection $rows) => $rows->keyBy('fiscal_year'));
            $plans = [];
            $originalVersions = [];
            $targetExisted = [];

            foreach ($companyIds as $companyId) {
                $state = $states[$companyId];
                $dirtyFrom = $state->dirty_from_year === null ? null : (int) $state->dirty_from_year;
                $companySnapshots = $snapshots->get($companyId, collect());
                $targetSnapshot = $companySnapshots->get($fiscalYear);
                $targetIsValid = $targetSnapshot
                    && (int) $targetSnapshot->source_version === (int) $state->ledger_version
                    && $targetSnapshot->source_through_date?->toDateString()
                        === Carbon::create($fiscalYear - 1, 12, 31)->toDateString()
                    && ($dirtyFrom === null || $fiscalYear < $dirtyFrom);
                $originalVersions[$companyId] = (int) $state->ledger_version;
                $targetExisted[$companyId] = (bool) $targetSnapshot;

                if ($targetIsValid) {
                    $statistics['skipped']++;

                    continue;
                }

                if ($dirtyFrom !== null && $dirtyFrom <= $fiscalYear) {
                    $startYear = $dirtyFrom;
                } else {
                    $latestPredecessor = $companySnapshots
                        ->keys()
                        ->map(fn ($year) => (int) $year)
                        ->filter(fn (int $year) => $year < $fiscalYear)
                        ->filter(function (int $year) use ($companySnapshots, $state, $dirtyFrom): bool {
                            $snapshot = $companySnapshots->get($year);

                            return $snapshot
                                && (int) $snapshot->source_version === (int) $state->ledger_version
                                && ($dirtyFrom === null || $year < $dirtyFrom);
                        })
                        ->max();
                    $startYear = $latestPredecessor ? $latestPredecessor + 1 : $fiscalYear;
                }

                $plans[$companyId] = range($startYear, $fiscalYear);
            }

            if ($plans === []) {
                return $statistics;
            }

            $records = [];
            $freshYears = [];
            $minimumYear = min(array_map(fn (array $years) => min($years), $plans));

            for ($year = $minimumYear; $year <= $fiscalYear; $year++) {
                $yearCompanyIds = collect($plans)
                    ->filter(fn (array $years) => in_array($year, $years, true))
                    ->keys()
                    ->map(fn ($id) => (int) $id)
                    ->sort()
                    ->values();

                if ($yearCompanyIds->isEmpty()) {
                    continue;
                }

                $movementCompanyIds = collect();
                $bootstrapCompanyIds = collect();

                foreach ($yearCompanyIds as $companyId) {
                    $predecessorYear = $year - 1;
                    $predecessor = $snapshots->get($companyId, collect())->get($predecessorYear);
                    $predecessorIsFresh = isset($freshYears[$companyId][$predecessorYear]);
                    $state = $states[$companyId];
                    $dirtyFrom = $state->dirty_from_year;
                    $predecessorIsValid = $predecessor
                        && (int) $predecessor->source_version === $originalVersions[$companyId]
                        && ($dirtyFrom === null || $predecessorYear < (int) $dirtyFrom || $predecessorIsFresh);

                    ($predecessorIsValid ? $movementCompanyIds : $bootstrapCompanyIds)->push($companyId);
                }

                $movementTotals = $this->aggregateTotals(
                    $ownerId,
                    $movementCompanyIds,
                    Carbon::create($year - 1, 1, 1)->toDateString(),
                    Carbon::create($year, 1, 1)->toDateString(),
                    $accountId
                    , $branchId
                    , $branchScoped
                );
                $bootstrapTotals = $this->aggregateTotals(
                    $ownerId,
                    $bootstrapCompanyIds,
                    null,
                    Carbon::create($year, 1, 1)->toDateString(),
                    $accountId
                    , $branchId
                    , $branchScoped
                );

                foreach ($yearCompanyIds as $companyId) {
                    $predecessor = $snapshots->get($companyId, collect())->get($year - 1);

                    if ($movementCompanyIds->contains($companyId) && $predecessor) {
                        $previousNet = DecimalAmount::subtract(
                            (string) $predecessor->opening_credit,
                            (string) $predecessor->opening_debit
                        );
                        $movement = $movementTotals->get($companyId, ['debit' => '0.00', 'credit' => '0.00']);
                        $net = DecimalAmount::add(
                            $previousNet,
                            $movement['credit'],
                            '-'.$movement['debit']
                        );
                    } else {
                        $total = $bootstrapTotals->get($companyId, ['debit' => '0.00', 'credit' => '0.00']);
                        $net = DecimalAmount::subtract($total['credit'], $total['debit']);
                    }

                    [$openingDebit, $openingCredit] = $this->splitPayableNet($net);
                    $record = [
                        'owner_id' => $ownerId,
                        ...$this->branchAttribute('supplier_debt_yearly_snapshots', $branchId),
                        'company_id' => $companyId,
                        'fiscal_year' => $year,
                        'opening_debit' => $openingDebit,
                        'opening_credit' => $openingCredit,
                        'source_through_date' => Carbon::create($year - 1, 12, 31)->toDateString(),
                        'source_version' => $originalVersions[$companyId],
                        'calculated_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $records[] = $record;
                    $freshYears[$companyId][$year] = true;

                    $model = new SupplierDebtYearlySnapshot($record);
                    $model->exists = true;
                    $snapshots->put(
                        $companyId,
                        $snapshots->get($companyId, collect())->put($year, $model)
                    );
                }
            }

            $currentVersions = SupplierDebtSnapshotState::query()
                ->where('owner_id', $ownerId)
                ->when($branchScoped, fn ($query) => $query->where('branch_id', $branchId))
                ->whereIn('company_id', array_keys($plans))
                ->pluck('ledger_version', 'company_id');

            foreach ($plans as $companyId => $years) {
                if ((int) $currentVersions[$companyId] !== $originalVersions[$companyId]) {
                    throw new RuntimeException('Ledger thay đổi trong lúc build snapshot; vui lòng retry.');
                }
            }

            foreach ($records as $record) {
                SupplierDebtYearlySnapshot::query()->updateOrCreate(
                    [
                        'owner_id' => $record['owner_id'],
                        'company_id' => $record['company_id'],
                        'fiscal_year' => $record['fiscal_year'],
                        ...$this->branchAttribute('supplier_debt_yearly_snapshots', $branchId),
                    ],
                    $record
                );
            }

            foreach ($plans as $companyId => $years) {
                $state = $states[$companyId];

                if ($state->dirty_from_year !== null && (int) $state->dirty_from_year <= $fiscalYear) {
                    $nextStaleYear = $snapshots->get($companyId, collect())
                        ->keys()
                        ->map(fn ($year) => (int) $year)
                        ->filter(fn (int $year) => $year > $fiscalYear)
                        ->min();
                    $state->forceFill(['dirty_from_year' => $nextStaleYear ?: null])->save();
                }

                $statistics[$targetExisted[$companyId] ? 'rebuilt' : 'built']++;
            }

            return $statistics;
        }, 3);
    }

    private function aggregateTotals(
        int $ownerId,
        Collection $companyIds,
        ?string $fromInclusive,
        string $toExclusive,
        ?int $accountId = null,
        ?int $branchId = null,
        bool $branchScoped = false
    ): Collection {
        if ($companyIds->isEmpty()) {
            return collect();
        }

        $accountId ??= $this->resolvePayableAccountId();

        return DB::table('transaction_entries as te')
            ->join('transactions as t', 't.id', '=', 'te.transaction_id')
            ->join('companies as c', function ($join) use ($ownerId): void {
                $join->on('c.id', '=', 'te.tableable_id')
                    ->where('c.user_id', $ownerId);
            })
            ->where('te.account_id', $accountId)
            ->where('te.tableable_type', Company::class)
            ->whereIn('te.tableable_id', $companyIds)
            ->where('t.user_id', $ownerId)
            ->where('t.status', 'completed')
            ->when($branchScoped, fn ($query) => $query->where('t.branch_id', $branchId))
            ->when($fromInclusive !== null, fn ($query) => $query->where('t.transaction_date', '>=', $fromInclusive))
            ->where('t.transaction_date', '<', $toExclusive)
            ->groupBy('te.tableable_id')
            ->select('te.tableable_id as company_id')
            ->selectRaw('CAST(COALESCE(SUM(te.debit_amount), 0) AS CHAR) AS debit_total')
            ->selectRaw('CAST(COALESCE(SUM(te.credit_amount), 0) AS CHAR) AS credit_total')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->company_id => [
                    'debit' => DecimalAmount::normalize((string) $row->debit_total),
                    'credit' => DecimalAmount::normalize((string) $row->credit_total),
                ],
            ]);
    }

    private function resolvePayableAccountId(): int
    {
        $accountId = Account::query()
            ->where('code', '331')
            ->where('status', true)
            ->value('id');

        if (! $accountId) {
            throw new RuntimeException('Canonical payable account 331 is missing or inactive.');
        }

        return (int) $accountId;
    }

    private function splitPayableNet(string $net): array
    {
        $net = DecimalAmount::normalize($net);

        if (DecimalAmount::compare($net, '0.00') > 0) {
            return ['0.00', $net];
        }

        if (DecimalAmount::compare($net, '0.00') < 0) {
            return [DecimalAmount::absolute($net), '0.00'];
        }

        return ['0.00', '0.00'];
    }

    private function validateFiscalYear(int $fiscalYear): void
    {
        if ($fiscalYear < 1970 || $fiscalYear > 2100) {
            throw new RuntimeException('Fiscal year must be between 1970 and 2100.');
        }
    }

    private function branchAttribute(string $table, ?int $branchId): array
    {
        return Schema::hasColumn($table, 'branch_id')
            ? ['branch_id' => $branchId]
            : [];
    }
}
