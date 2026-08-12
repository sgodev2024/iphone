<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Client;
use App\Models\CustomerDebtSnapshotState;
use App\Models\CustomerDebtYearlySnapshot;
use App\Support\DecimalAmount;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CustomerDebtSnapshotService
{
    public function report(
        int $ownerId,
        string $fromDate,
        string $toDate,
        ?string $nameFilter = null
    ): Collection {
        $from = Carbon::parse($fromDate);
        $fiscalYear = $from->year;
        $yearStart = $from->copy()->startOfYear()->toDateString();
        $accountId = $this->resolveReceivableAccountId();

        $this->buildOwnerYear($ownerId, $fiscalYear, 1000, $nameFilter);

        $ledger = DB::table('transaction_entries as te')
            ->join('transactions as t', 't.id', '=', 'te.transaction_id')
            ->join('clients as ledger_clients', function ($join) use ($ownerId): void {
                $join->on('ledger_clients.id', '=', 'te.tableable_id')
                    ->where('ledger_clients.user_id', $ownerId);
            })
            ->where('te.account_id', $accountId)
            ->where('te.tableable_type', Client::class)
            ->where('t.transaction_date', '>=', $yearStart)
            ->where('t.transaction_date', '<=', $toDate)
            ->groupBy('te.tableable_id')
            ->select('te.tableable_id as client_id')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN t.transaction_date < ? THEN te.debit_amount ELSE 0 END), 0) AS before_debit',
                [$fromDate]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN t.transaction_date < ? THEN te.credit_amount ELSE 0 END), 0) AS before_credit',
                [$fromDate]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN t.transaction_date >= ? THEN te.debit_amount ELSE 0 END), 0) AS period_debit',
                [$fromDate]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN t.transaction_date >= ? THEN te.credit_amount ELSE 0 END), 0) AS period_credit',
                [$fromDate]
            );

        return DB::table('clients as c')
            ->join('customer_debt_yearly_snapshots as s', function ($join) use ($ownerId, $fiscalYear): void {
                $join->on('s.client_id', '=', 'c.id')
                    ->where('s.owner_id', $ownerId)
                    ->where('s.fiscal_year', $fiscalYear);
            })
            ->leftJoinSub($ledger, 'l', fn ($join) => $join->on('l.client_id', '=', 'c.id'))
            ->where('c.user_id', $ownerId)
            ->when($nameFilter !== null && $nameFilter !== '', fn ($query) => $query->where('c.name', 'like', "%{$nameFilter}%"))
            ->orderBy('c.name')
            ->orderBy('c.id')
            ->get([
                'c.id',
                'c.code',
                'c.name',
                'c.phone',
                's.opening_debit',
                's.opening_credit',
                DB::raw('COALESCE(l.before_debit, 0) AS before_debit'),
                DB::raw('COALESCE(l.before_credit, 0) AS before_credit'),
                DB::raw('COALESCE(l.period_debit, 0) AS period_debit'),
                DB::raw('COALESCE(l.period_credit, 0) AS period_credit'),
            ])
            ->map(function ($client): object {
                $openingNet = DecimalAmount::subtract(
                    DecimalAmount::add((string) $client->opening_debit, (string) $client->before_debit),
                    DecimalAmount::add((string) $client->opening_credit, (string) $client->before_credit)
                );
                $periodDebit = DecimalAmount::normalize((string) $client->period_debit);
                $periodCredit = DecimalAmount::normalize((string) $client->period_credit);
                $endingNet = DecimalAmount::subtract(
                    DecimalAmount::add($openingNet, $periodDebit),
                    $periodCredit
                );
                $opening = DecimalAmount::splitNet($openingNet);
                $ending = DecimalAmount::splitNet($endingNet);

                return (object) [
                    'client_id' => (int) $client->id,
                    'client_code' => $client->code,
                    'client_name' => $client->name,
                    'client_phone' => $client->phone,
                    'opening_debit' => $opening['debit'],
                    'opening_credit' => $opening['credit'],
                    'period_debit' => $periodDebit,
                    'period_credit' => $periodCredit,
                    'ending_debit' => $ending['debit'],
                    'ending_credit' => $ending['credit'],
                ];
            })
            ->filter(fn ($item) => ! DecimalAmount::isZero($item->opening_debit)
                || ! DecimalAmount::isZero($item->opening_credit)
                || ! DecimalAmount::isZero($item->period_debit)
                || ! DecimalAmount::isZero($item->period_credit))
            ->values();
    }

    public function getOrBuild(int $ownerId, int $clientId, int $fiscalYear): CustomerDebtYearlySnapshot
    {
        $client = Client::withTrashed()
            ->whereKey($clientId)
            ->where('user_id', $ownerId)
            ->firstOrFail();

        $this->buildClientChunk($ownerId, collect([(int) $client->id]), $fiscalYear);

        return CustomerDebtYearlySnapshot::query()
            ->where('owner_id', $ownerId)
            ->where('client_id', $clientId)
            ->where('fiscal_year', $fiscalYear)
            ->firstOrFail();
    }

    public function buildOwnerYear(
        int $ownerId,
        int $fiscalYear,
        int $chunkSize = 1000,
        ?string $nameFilter = null
    ): array {
        $this->resolveReceivableAccountId();
        $statistics = [
            'scanned' => 0,
            'built' => 0,
            'rebuilt' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        DB::table('clients')
            ->where('user_id', $ownerId)
            ->when($nameFilter !== null && $nameFilter !== '', fn ($query) => $query->where('name', 'like', "%{$nameFilter}%"))
            ->orderBy('id')
            ->chunkById(max(1, $chunkSize), function (Collection $clients) use (
                $ownerId,
                $fiscalYear,
                &$statistics
            ): void {
                $clientIds = $clients->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
                $chunkStatistics = $this->buildClientChunk($ownerId, $clientIds, $fiscalYear);

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
        $openingYear = $ledgerYear + 1;
        $targetYear = max(
            now()->year,
            (int) CustomerDebtYearlySnapshot::query()
                ->where('owner_id', $ownerId)
                ->max('fiscal_year')
        );
        $now = now();

        DB::table('clients')
            ->where('user_id', $ownerId)
            ->orderBy('id')
            ->chunkById(max(1, $chunkSize), function (Collection $clients) use ($ownerId, $openingYear, $now): void {
                $clientIds = $clients->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
                $rows = $clientIds->map(fn (int $clientId): array => [
                    'owner_id' => $ownerId,
                    'client_id' => $clientId,
                    'ledger_version' => 0,
                    'dirty_from_year' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::transaction(function () use ($rows, $ownerId, $clientIds, $openingYear): void {
                    DB::table('customer_debt_snapshot_states')->insertOrIgnore($rows);
                    $states = CustomerDebtSnapshotState::query()
                        ->where('owner_id', $ownerId)
                        ->whereIn('client_id', $clientIds)
                        ->orderBy('client_id')
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

    public function fullLedgerOpeningNet(int $clientId, int $fiscalYear): string
    {
        return $this->fullLedgerOpeningNets(collect([$clientId]), $fiscalYear)
            ->get($clientId, '0.00');
    }

    public function fullLedgerOpeningNets(Collection $clientIds, int $fiscalYear): Collection
    {
        return $this->aggregateTotals(
            $clientIds,
            null,
            Carbon::create($fiscalYear, 1, 1)->toDateString()
        )->map(fn (array $totals): string => DecimalAmount::subtract(
            $totals['debit'],
            $totals['credit']
        ));
    }

    private function buildClientChunk(int $ownerId, Collection $clientIds, int $fiscalYear): array
    {
        $statistics = [
            'scanned' => $clientIds->count(),
            'built' => 0,
            'rebuilt' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        if ($clientIds->isEmpty()) {
            return $statistics;
        }

        $accountId = $this->resolveReceivableAccountId();

        return DB::transaction(function () use (
            $ownerId,
            $clientIds,
            $fiscalYear,
            $accountId,
            $statistics
        ): array {
            $now = now();
            $stateRows = $clientIds->map(fn (int $clientId): array => [
                'owner_id' => $ownerId,
                'client_id' => $clientId,
                'ledger_version' => 0,
                'dirty_from_year' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();
            DB::table('customer_debt_snapshot_states')->insertOrIgnore($stateRows);

            $states = CustomerDebtSnapshotState::query()
                ->where('owner_id', $ownerId)
                ->whereIn('client_id', $clientIds)
                ->orderBy('client_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('client_id');

            if ($states->count() !== $clientIds->count()) {
                throw new RuntimeException('Không thể khóa đầy đủ trạng thái snapshot công nợ khách hàng.');
            }

            $snapshots = CustomerDebtYearlySnapshot::query()
                ->where('owner_id', $ownerId)
                ->whereIn('client_id', $clientIds)
                ->orderBy('client_id')
                ->orderBy('fiscal_year')
                ->get()
                ->groupBy('client_id')
                ->map(fn (EloquentCollection $rows) => $rows->keyBy('fiscal_year'));
            $plans = [];
            $originalVersions = [];
            $targetExisted = [];

            foreach ($clientIds as $clientId) {
                $state = $states[$clientId];
                $dirtyFrom = $state->dirty_from_year === null ? null : (int) $state->dirty_from_year;
                $clientSnapshots = $snapshots->get($clientId, collect());
                $targetSnapshot = $clientSnapshots->get($fiscalYear);
                $targetIsValid = $targetSnapshot && ($dirtyFrom === null || $fiscalYear < $dirtyFrom);
                $originalVersions[$clientId] = (int) $state->ledger_version;
                $targetExisted[$clientId] = (bool) $targetSnapshot;

                if ($targetIsValid) {
                    $statistics['skipped']++;

                    continue;
                }

                if ($dirtyFrom !== null && $dirtyFrom <= $fiscalYear) {
                    $startYear = $dirtyFrom;
                } else {
                    $latestPredecessor = $clientSnapshots
                        ->keys()
                        ->map(fn ($year) => (int) $year)
                        ->filter(fn (int $year) => $year < $fiscalYear && ($dirtyFrom === null || $year < $dirtyFrom))
                        ->max();
                    $startYear = $latestPredecessor ? $latestPredecessor + 1 : $fiscalYear;
                }

                $plans[$clientId] = range($startYear, $fiscalYear);
            }

            if ($plans === []) {
                return $statistics;
            }

            $records = [];
            $freshYears = [];
            $minimumYear = min(array_map(fn (array $years) => min($years), $plans));

            for ($year = $minimumYear; $year <= $fiscalYear; $year++) {
                $yearClientIds = collect($plans)
                    ->filter(fn (array $years) => in_array($year, $years, true))
                    ->keys()
                    ->map(fn ($id) => (int) $id)
                    ->sort()
                    ->values();

                if ($yearClientIds->isEmpty()) {
                    continue;
                }

                $movementClientIds = collect();
                $bootstrapClientIds = collect();

                foreach ($yearClientIds as $clientId) {
                    $predecessorYear = $year - 1;
                    $predecessor = $snapshots->get($clientId, collect())->get($predecessorYear);
                    $predecessorIsFresh = isset($freshYears[$clientId][$predecessorYear]);
                    $dirtyFrom = $states[$clientId]->dirty_from_year;
                    $predecessorIsValid = $predecessor
                        && ($dirtyFrom === null || $predecessorYear < (int) $dirtyFrom || $predecessorIsFresh);

                    ($predecessorIsValid ? $movementClientIds : $bootstrapClientIds)->push($clientId);
                }

                $movementTotals = $this->aggregateTotals(
                    $movementClientIds,
                    Carbon::create($year - 1, 1, 1)->toDateString(),
                    Carbon::create($year, 1, 1)->toDateString(),
                    $accountId
                );
                $bootstrapTotals = $this->aggregateTotals(
                    $bootstrapClientIds,
                    null,
                    Carbon::create($year, 1, 1)->toDateString(),
                    $accountId
                );

                foreach ($yearClientIds as $clientId) {
                    $predecessor = $snapshots->get($clientId, collect())->get($year - 1);

                    if ($movementClientIds->contains($clientId) && $predecessor) {
                        $previousNet = DecimalAmount::subtract(
                            (string) $predecessor->opening_debit,
                            (string) $predecessor->opening_credit
                        );
                        $movement = $movementTotals->get($clientId, ['debit' => '0.00', 'credit' => '0.00']);
                        $net = DecimalAmount::subtract(
                            DecimalAmount::add($previousNet, $movement['debit']),
                            $movement['credit']
                        );
                    } else {
                        $total = $bootstrapTotals->get($clientId, ['debit' => '0.00', 'credit' => '0.00']);
                        $net = DecimalAmount::subtract($total['debit'], $total['credit']);
                    }

                    $split = DecimalAmount::splitNet($net);
                    $record = [
                        'owner_id' => $ownerId,
                        'client_id' => $clientId,
                        'fiscal_year' => $year,
                        'opening_debit' => $split['debit'],
                        'opening_credit' => $split['credit'],
                        'source_through_date' => Carbon::create($year - 1, 12, 31)->toDateString(),
                        'source_version' => $originalVersions[$clientId],
                        'calculated_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $records[] = $record;
                    $freshYears[$clientId][$year] = true;

                    $model = new CustomerDebtYearlySnapshot($record);
                    $model->exists = true;
                    $snapshots->put(
                        $clientId,
                        $snapshots->get($clientId, collect())->put($year, $model)
                    );
                }
            }

            $currentVersions = CustomerDebtSnapshotState::query()
                ->where('owner_id', $ownerId)
                ->whereIn('client_id', array_keys($plans))
                ->pluck('ledger_version', 'client_id');

            foreach ($plans as $clientId => $years) {
                if ((int) $currentVersions[$clientId] !== $originalVersions[$clientId]) {
                    throw new RuntimeException('Ledger thay đổi trong lúc build snapshot; vui lòng retry.');
                }
            }

            CustomerDebtYearlySnapshot::query()->upsert(
                $records,
                ['owner_id', 'client_id', 'fiscal_year'],
                [
                    'opening_debit',
                    'opening_credit',
                    'source_through_date',
                    'source_version',
                    'calculated_at',
                    'updated_at',
                ]
            );

            foreach ($plans as $clientId => $years) {
                $state = $states[$clientId];

                if ($state->dirty_from_year !== null && (int) $state->dirty_from_year <= $fiscalYear) {
                    $nextStaleYear = $snapshots->get($clientId, collect())
                        ->keys()
                        ->map(fn ($year) => (int) $year)
                        ->filter(fn (int $year) => $year > $fiscalYear)
                        ->min();
                    $state->forceFill(['dirty_from_year' => $nextStaleYear ?: null])->save();
                }

                $statistics[$targetExisted[$clientId] ? 'rebuilt' : 'built']++;
            }

            return $statistics;
        }, 3);
    }

    private function aggregateTotals(
        Collection $clientIds,
        ?string $fromInclusive,
        string $toExclusive,
        ?int $accountId = null
    ): Collection {
        if ($clientIds->isEmpty()) {
            return collect();
        }

        $accountId ??= $this->resolveReceivableAccountId();

        return DB::table('transaction_entries as te')
            ->join('transactions as t', 't.id', '=', 'te.transaction_id')
            ->where('te.account_id', $accountId)
            ->where('te.tableable_type', Client::class)
            ->whereIn('te.tableable_id', $clientIds)
            ->when($fromInclusive !== null, fn ($query) => $query->where('t.transaction_date', '>=', $fromInclusive))
            ->where('t.transaction_date', '<', $toExclusive)
            ->groupBy('te.tableable_id')
            ->select('te.tableable_id as client_id')
            ->selectRaw('COALESCE(SUM(te.debit_amount), 0) AS debit_total')
            ->selectRaw('COALESCE(SUM(te.credit_amount), 0) AS credit_total')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->client_id => [
                    'debit' => DecimalAmount::normalize((string) $row->debit_total),
                    'credit' => DecimalAmount::normalize((string) $row->credit_total),
                ],
            ]);
    }

    private function resolveReceivableAccountId(): int
    {
        $accountId = Account::query()
            ->where('code', '131')
            ->where('status', true)
            ->value('id');

        if (! $accountId) {
            throw new RuntimeException('Không tìm thấy tài khoản phải thu khách hàng (131) đang hoạt động.');
        }

        return (int) $accountId;
    }
}
