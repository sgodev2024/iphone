<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Services\Accounting\CustomerDebtSnapshotInvalidator;
use App\Services\Accounting\SupplierDebtSnapshotInvalidator;
use Illuminate\Support\Facades\Schema;

class TransactionEntryObserver
{
    public function created(TransactionEntry $entry): void
    {
        $contributions = [$this->contribution($entry->getAttributes())];
        app(CustomerDebtSnapshotInvalidator::class)->invalidateMany($contributions);
        app(SupplierDebtSnapshotInvalidator::class)->invalidateMany($contributions);
    }

    public function updated(TransactionEntry $entry): void
    {
        if (! $entry->wasChanged([
            'transaction_id',
            'account_id',
            'debit_amount',
            'credit_amount',
            'tableable_type',
            'tableable_id',
        ])) {
            return;
        }

        $contributions = [
            $this->contribution($entry->getOriginal()),
            $this->contribution($entry->getAttributes()),
        ];
        app(CustomerDebtSnapshotInvalidator::class)->invalidateMany($contributions);
        app(SupplierDebtSnapshotInvalidator::class)->invalidateMany($contributions);
    }

    public function deleted(TransactionEntry $entry): void
    {
        $contributions = [$this->contribution($entry->getOriginal())];
        app(CustomerDebtSnapshotInvalidator::class)->invalidateMany($contributions);
        app(SupplierDebtSnapshotInvalidator::class)->invalidateMany($contributions);
    }

    private function contribution(array $attributes): array
    {
        $transactionId = (int) ($attributes['transaction_id'] ?? 0);
        $hasBranchId = Schema::hasColumn('transactions', 'branch_id');
        $columns = ['transaction_date', 'status', 'user_id'];

        if ($hasBranchId) {
            $columns[] = 'branch_id';
        }

        $transaction = Transaction::query()
            ->whereKey($transactionId)
            ->first($columns);

        return [
            'accountId' => (int) ($attributes['account_id'] ?? 0),
            'tableableType' => $attributes['tableable_type'] ?? null,
            'tableableId' => isset($attributes['tableable_id']) ? (int) $attributes['tableable_id'] : null,
            'transactionDate' => $transaction?->transaction_date,
            'transactionStatus' => $transaction?->status,
            'transactionOwnerId' => $transaction?->user_id,
            'transactionBranchId' => $hasBranchId ? $transaction?->branch_id : null,
        ];
    }
}
