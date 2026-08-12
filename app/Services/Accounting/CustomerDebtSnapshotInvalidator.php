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
        $dirtyByClient = [];

        foreach ($contributions as $contribution) {
            $accountId = (int) ($contribution['accountId'] ?? 0);
            $clientId = (int) ($contribution['tableableId'] ?? 0);
            $transactionDate = $contribution['transactionDate'] ?? null;

            if (! in_array($accountId, $receivableAccountIds, true)
                || ($contribution['tableableType'] ?? null) !== Client::class
                || ! $transactionDate
                || ! $clients->has($clientId)
            ) {
                continue;
            }

            $dirtyYear = Carbon::parse($transactionDate)->year + 1;
            $dirtyByClient[$clientId] = isset($dirtyByClient[$clientId])
                ? min($dirtyByClient[$clientId], $dirtyYear)
                : $dirtyYear;
        }

        if ($dirtyByClient === []) {
            return;
        }

        $now = now();
        $rows = collect(array_keys($dirtyByClient))
            ->sort()
            ->map(function (int $clientId) use ($clients, $now): array {
                return [
                    'owner_id' => (int) $clients[$clientId]->user_id,
                    'client_id' => $clientId,
                    'ledger_version' => 0,
                    'dirty_from_year' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->all();

        DB::table('customer_debt_snapshot_states')->insertOrIgnore($rows);

        foreach (collect(array_keys($dirtyByClient))->sort() as $clientId) {
            $client = $clients[$clientId];
            $state = CustomerDebtSnapshotState::query()
                ->where('owner_id', $client->user_id)
                ->where('client_id', $clientId)
                ->lockForUpdate()
                ->firstOrFail();
            $dirtyYear = $dirtyByClient[$clientId];

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
