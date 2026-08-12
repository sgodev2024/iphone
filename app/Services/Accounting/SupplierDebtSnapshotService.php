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
use RuntimeException;

class SupplierDebtSnapshotService
{
    public function reportOpenings(
        int $ownerId,
        int $fiscalYear,
        int $chunkSize = 1000,
        ?string $nameFilter = null
    ): Collection {
        $this->validateFiscalYear($fiscalYear);
        $this->buildOwnerYear($ownerId, $fiscalYear, $chunkSize, $nameFilter);

        return DB::table('companies as c')
            ->join('supplier_debt_yearly_snapshots as s', function ($join) use ($ownerId, $fiscalYear): void {
                $join->on('s.company_id', '=', 'c.id')
                    ->where('s.owner_id', $ownerId)
                    ->where('s.fiscal_year', $fiscalYear);
            })
            ->where('c.user_id', $ownerId)
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

        $this->buildCompanyChunk($ownerId, collect([(int) $company->id]), $fiscalYear);

        return SupplierDebtYearlySnapshot::query()
            ->where('owner_id', $ownerId)
            ->where('company_id', $companyId)
            ->where('fiscal_year', $fiscalYear)
            ->firstOrFail();
    }

    public function buildOwnerYear(
        int $ownerId,
        int $fiscalYear,
        int $chunkSize = 1000,
        ?string $nameFilter = null
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
            ->when($nameFilter !== null && trim($nameFilter) !== '', function ($query) use ($nameFilter): void {
                $query->where('name', 'like', '%'.trim($nameFilter).'%');
            })
            ->orderBy('id')
            ->chunkById(max(1, $chunkSize), function (Collection $companies) use (
                $ownerId,
                $fiscalYear,
                $accountId,
                &$statistics
            ): void {
                $companyIds = $companies->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
                $chunkStatistics = $this->buildCompanyChunk($ownerId, $companyIds, $fiscalYear, $accountId);

                foreach ($statistics as $key => $value) {
                    $statistics[$key] += $chunkStatistics[$key];
                }
            }, 'id');

        return $statistics;
    }

    public function rebuildOwnerFromLedgerYear(
        int $ownerId,
        int $ledgerYear,
        int $chunkSize = 1000
    ): array {
        $this->validateFiscalYear($ledgerYear);
        $openingYear = $ledgerYear + 1;
        $targetYear = max(
            now()->year,
            $openingYear,
            (int) SupplierDebtYearlySnapshot::query()
                ->where('owner_id', $ownerId)
                ->max('fiscal_year')
        );
        $now = now();

        DB::table('companies')
            ->where('user_id', $ownerId)
            ->orderBy('id')
            ->chunkById(max(1, $chunkSize), function (Collection $companies) use (
                $ownerId,
                $openingYear,
                $now
            ): void {
                $companyIds = $companies->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
                $rows = $companyIds->map(fn (int $companyId): array => [
                    'owner_id' => $ownerId,
                    'company_id' => $companyId,
                    'ledger_version' => 0,
                    'dirty_from_year' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::transaction(function () use ($rows, $ownerId, $companyIds, $openingYear): void {
                    DB::table('supplier_debt_snapshot_states')->insertOrIgnore($rows);
                    $states = SupplierDebtSnapshotState::query()
                        ->where('owner_id', $ownerId)
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

        return $this->buildOwnerYear($ownerId, $targetYear, $chunkSize);
    }

    public function fullLedgerOpeningNets(
        int $ownerId,
        Collection $companyIds,
        int $fiscalYear
    ): Collection {
        return $this->aggregateTotals(
            $ownerId,
            $companyIds,
            null,
            Carbon::create($fiscalYear, 1, 1)->toDateString()
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
            (int) $snapshot->fiscal_year
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
        ?int $accountId = null
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
            $statistics
        ): array {
            $now = now();
            $stateRows = $companyIds->map(fn (int $companyId): array => [
                'owner_id' => $ownerId,
                'company_id' => $companyId,
                'ledger_version' => 0,
                'dirty_from_year' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();
            DB::table('supplier_debt_snapshot_states')->insertOrIgnore($stateRows);

            $states = SupplierDebtSnapshotState::query()
                ->where('owner_id', $ownerId)
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
                    && (string) $targetSnapshot->source_through_date === Carbon::create($fiscalYear - 1, 12, 31)->toDateString()
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
                );
                $bootstrapTotals = $this->aggregateTotals(
                    $ownerId,
                    $bootstrapCompanyIds,
                    null,
                    Carbon::create($year, 1, 1)->toDateString(),
                    $accountId
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
                ->whereIn('company_id', array_keys($plans))
                ->pluck('ledger_version', 'company_id');

            foreach ($plans as $companyId => $years) {
                if ((int) $currentVersions[$companyId] !== $originalVersions[$companyId]) {
                    throw new RuntimeException('Ledger thay đổi trong lúc build snapshot; vui lòng retry.');
                }
            }

            SupplierDebtYearlySnapshot::query()->upsert(
                $records,
                ['owner_id', 'company_id', 'fiscal_year'],
                [
                    'opening_debit',
                    'opening_credit',
                    'source_through_date',
                    'source_version',
                    'calculated_at',
                    'updated_at',
                ]
            );

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
        ?int $accountId = null
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
}
