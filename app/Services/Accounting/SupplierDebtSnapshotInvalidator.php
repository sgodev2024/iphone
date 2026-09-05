<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\SupplierDebtSnapshotState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SupplierDebtSnapshotInvalidator
{
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
        $payableAccountIds = Account::query()
            ->whereIn('id', $accountIds)
            ->where('code', '331')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($payableAccountIds === []) {
            return;
        }

        $companyIds = collect($contributions)
            ->filter(fn (array $item): bool => in_array((int) ($item['accountId'] ?? 0), $payableAccountIds, true))
            ->filter(fn (array $item): bool => ($item['tableableType'] ?? null) === Company::class)
            ->pluck('tableableId')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();

        if ($companyIds->isEmpty()) {
            return;
        }

        $companies = Company::query()
            ->whereIn('id', $companyIds)
            ->get(['id', 'user_id'])
            ->keyBy('id');
        $dirtyByCompany = [];

        foreach ($contributions as $contribution) {
            $accountId = (int) ($contribution['accountId'] ?? 0);
            $companyId = (int) ($contribution['tableableId'] ?? 0);
            $transactionDate = $contribution['transactionDate'] ?? null;
            $transactionStatus = (string) ($contribution['transactionStatus'] ?? '');
            $transactionOwnerId = (int) ($contribution['transactionOwnerId'] ?? 0);
            $branchId = isset($contribution['transactionBranchId'])
                ? (int) $contribution['transactionBranchId']
                : null;

            if (! in_array($accountId, $payableAccountIds, true)
                || ($contribution['tableableType'] ?? null) !== Company::class
                || ! $transactionDate
                || $transactionStatus !== 'completed'
                || ! $transactionOwnerId
                || ! $companies->has($companyId)
            ) {
                continue;
            }

            $company = $companies[$companyId];
            if ((int) $company->user_id !== $transactionOwnerId) {
                Log::warning('Skipped cross-owner supplier ledger invalidation.', [
                    'company_id' => $companyId,
                    'company_owner_id' => (int) $company->user_id,
                    'transaction_owner_id' => $transactionOwnerId,
                ]);

                continue;
            }

            $dirtyYear = Carbon::parse($transactionDate)->year + 1;
            $key = $transactionOwnerId.':'.$companyId.':'.($branchId ?? 'null');
            $dirtyByCompany[$key] = [
                'owner_id' => $transactionOwnerId,
                'branch_id' => $branchId,
                'company_id' => $companyId,
                'dirty_from_year' => isset($dirtyByCompany[$key])
                    ? min($dirtyByCompany[$key]['dirty_from_year'], $dirtyYear)
                    : $dirtyYear,
            ];
        }

        if ($dirtyByCompany === []) {
            return;
        }

        DB::transaction(function () use ($dirtyByCompany): void {
            $now = now();
            $branchAware = Schema::hasColumn('supplier_debt_snapshot_states', 'branch_id');
            $rows = collect($dirtyByCompany)
                ->sortBy(fn (array $row): string => $row['owner_id'].':'.$row['company_id'].':'.($row['branch_id'] ?? 'null'))
                ->map(fn (array $row): array => [
                    'owner_id' => $row['owner_id'],
                    ...($branchAware ? ['branch_id' => $row['branch_id']] : []),
                    'company_id' => $row['company_id'],
                    'ledger_version' => 0,
                    'dirty_from_year' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->values()
                ->all();
            foreach ($rows as $row) {
                $stateQuery = DB::table('supplier_debt_snapshot_states')->where([
                    'owner_id' => $row['owner_id'],
                    'company_id' => $row['company_id'],
                    ...($branchAware ? ['branch_id' => $row['branch_id']] : []),
                ]);
                if (! $stateQuery->exists()) {
                    DB::table('supplier_debt_snapshot_states')->insert($row);
                }
            }

            foreach (collect($dirtyByCompany)->sortBy(fn (array $row): string => $row['owner_id'].':'.$row['company_id'].':'.($row['branch_id'] ?? 'null')) as $row) {
                $state = SupplierDebtSnapshotState::query()
                    ->where('owner_id', $row['owner_id'])
                    ->when($branchAware, fn ($query) => $query->where('branch_id', $row['branch_id']))
                    ->where('company_id', $row['company_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $state->forceFill([
                    'ledger_version' => (int) $state->ledger_version + 1,
                    'dirty_from_year' => $state->dirty_from_year === null
                        ? $row['dirty_from_year']
                        : min((int) $state->dirty_from_year, $row['dirty_from_year']),
                ])->save();
            }
        }, 3);
    }

    private function isAvailable(): bool
    {
        return Schema::hasTable('supplier_debt_snapshot_states')
            && Schema::hasTable('accounts')
            && Schema::hasTable('companies');
    }
}
