<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Client;
use App\Models\CustomerDebtSnapshotState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerDebtSnapshotInvalidator
{
    public function invalidate(
        int $accountId,
        ?string $tableableType,
        ?int $tableableId,
        mixed $transactionDate
    ): void {
        $this->invalidateMany([compact(
            'accountId',
            'tableableType',
            'tableableId',
            'transactionDate'
        )]);
    }

    public function invalidateMany(array $contributions): void
    {
        if (! $this->isAvailable() || $contributions === []) {
            return;
        }

        $accountIds = collect($contributions)
            ->pluck('accountId')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();
        $receivableAccountIds = Account::query()
            ->whereIn('id', $accountIds)
            ->where('code', '131')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $clientIds = collect($contributions)
            ->filter(fn (array $item) => in_array((int) ($item['accountId'] ?? 0), $receivableAccountIds, true))
            ->filter(fn (array $item) => ($item['tableableType'] ?? null) === Client::class)
            ->pluck('tableableId')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();

        if ($clientIds->isEmpty()) {
            return;
        }

        $clients = Client::withTrashed()
            ->whereIn('id', $clientIds)
            ->get(['id', 'user_id'])
            ->keyBy('id');
        $dirtyStates = [];

        foreach ($contributions as $contribution) {
            $accountId = (int) ($contribution['accountId'] ?? 0);
            $clientId = (int) ($contribution['tableableId'] ?? 0);
            $transactionDate = $contribution['transactionDate'] ?? null;
            $branchId = isset($contribution['transactionBranchId'])
                ? (int) $contribution['transactionBranchId']
                : null;

            if (! in_array($accountId, $receivableAccountIds, true)
                || ($contribution['tableableType'] ?? null) !== Client::class
                || ! $transactionDate
                || ! $clients->has($clientId)
            ) {
                continue;
            }

            $dirtyYear = Carbon::parse($transactionDate)->year + 1;
            $stateKey = $clientId.':'.($branchId ?? 'null');
            $dirtyStates[$stateKey] = [
                'client_id' => $clientId,
                'branch_id' => $branchId,
                'dirty_year' => isset($dirtyStates[$stateKey])
                    ? min($dirtyStates[$stateKey]['dirty_year'], $dirtyYear)
                    : $dirtyYear,
            ];
        }

        if ($dirtyStates === []) {
            return;
        }

        $now = now();
        $branchAware = Schema::hasColumn('customer_debt_snapshot_states', 'branch_id');
        $rows = collect($dirtyStates)
            ->sortBy(fn (array $item) => [$item['client_id'], $item['branch_id']])
            ->map(function (array $item) use ($clients, $now, $branchAware): array {
                $clientId = $item['client_id'];
                return [
                    'owner_id' => (int) $clients[$clientId]->user_id,
                    ...($branchAware ? ['branch_id' => $item['branch_id']] : []),
                    'client_id' => $clientId,
                    'ledger_version' => 0,
                    'dirty_from_year' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->all();

        foreach ($rows as $row) {
            $stateQuery = DB::table('customer_debt_snapshot_states')->where([
                'owner_id' => $row['owner_id'],
                'client_id' => $row['client_id'],
                ...($branchAware ? ['branch_id' => $row['branch_id']] : []),
            ]);
            if (! $stateQuery->exists()) {
                DB::table('customer_debt_snapshot_states')->insert($row);
            }
        }

        foreach (collect($dirtyStates)->sortBy(fn (array $item) => [$item['client_id'], $item['branch_id']]) as $item) {
            $clientId = $item['client_id'];
            $client = $clients[$clientId];
            $state = CustomerDebtSnapshotState::query()
                ->where('owner_id', $client->user_id)
                ->when($branchAware, fn ($query) => $query->where('branch_id', $item['branch_id']))
                ->where('client_id', $clientId)
                ->lockForUpdate()
                ->firstOrFail();
            $dirtyYear = $item['dirty_year'];

            $state->forceFill([
                'ledger_version' => (int) $state->ledger_version + 1,
                'dirty_from_year' => $state->dirty_from_year === null
                    ? $dirtyYear
                    : min((int) $state->dirty_from_year, $dirtyYear),
            ])->save();
        }
    }

    private function isAvailable(): bool
    {
        return Schema::hasTable('customer_debt_snapshot_states')
            && Schema::hasTable('accounts')
            && Schema::hasTable('clients');
    }
}
