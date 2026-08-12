<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Services\Accounting\CustomerDebtSnapshotInvalidator;
use App\Services\Accounting\SupplierDebtSnapshotInvalidator;
use Illuminate\Support\Facades\Schema;

class TransactionObserver
{
    public function updated(Transaction $transaction): void
    {
        if (! $transaction->wasChanged([
            'transaction_date',
            'status',
            'user_id',
        ])) {
            return;
        }

        if (! Schema::hasTable('transaction_entries')) {
            return;
        }

        $oldDate = $transaction->getOriginal('transaction_date');
        $newDate = $transaction->getAttribute('transaction_date');
        $oldStatus = $transaction->getOriginal('status');
        $newStatus = $transaction->getAttribute('status');
        $oldOwnerId = $transaction->getOriginal('user_id');
        $newOwnerId = $transaction->getAttribute('user_id');
        $contributions = [];

        foreach ($transaction->entries()->get() as $entry) {
            $contributions[] = [
                'accountId' => (int) $entry->account_id,
                'tableableType' => $entry->tableable_type,
                'tableableId' => $entry->tableable_id ? (int) $entry->tableable_id : null,
                'transactionDate' => $oldDate,
                'transactionStatus' => $oldStatus,
                'transactionOwnerId' => $oldOwnerId,
            ];
            $contributions[] = [
                'accountId' => (int) $entry->account_id,
                'tableableType' => $entry->tableable_type,
                'tableableId' => $entry->tableable_id ? (int) $entry->tableable_id : null,
                'transactionDate' => $newDate,
                'transactionStatus' => $newStatus,
                'transactionOwnerId' => $newOwnerId,
            ];
        }

        app(CustomerDebtSnapshotInvalidator::class)->invalidateMany($contributions);
        app(SupplierDebtSnapshotInvalidator::class)->invalidateMany($contributions);
    }

    public function deleting(Transaction $transaction): void
    {
        if (! Schema::hasTable('transaction_entries')) {
            return;
        }

        $transactionDate = $transaction->getAttribute('transaction_date');
        $contributions = $transaction->entries()->get()->map(fn ($entry): array => [
            'accountId' => (int) $entry->account_id,
            'tableableType' => $entry->tableable_type,
            'tableableId' => $entry->tableable_id ? (int) $entry->tableable_id : null,
            'transactionDate' => $transactionDate,
            'transactionStatus' => $transaction->getAttribute('status'),
            'transactionOwnerId' => $transaction->getAttribute('user_id'),
        ])->all();

        app(CustomerDebtSnapshotInvalidator::class)->invalidateMany($contributions);
        app(SupplierDebtSnapshotInvalidator::class)->invalidateMany($contributions);
    }
}
