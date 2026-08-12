<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Services\Accounting\CustomerDebtSnapshotInvalidator;

class TransactionObserver
{
    public function updated(Transaction $transaction): void
    {
        if (! $transaction->wasChanged('transaction_date')) {
            return;
        }

        $oldDate = $transaction->getOriginal('transaction_date');
        $newDate = $transaction->getAttribute('transaction_date');
        $contributions = [];

        foreach ($transaction->entries()->get() as $entry) {
            $contributions[] = [
                'accountId' => (int) $entry->account_id,
                'tableableType' => $entry->tableable_type,
                'tableableId' => $entry->tableable_id ? (int) $entry->tableable_id : null,
                'transactionDate' => $oldDate,
            ];
            $contributions[] = [
                'accountId' => (int) $entry->account_id,
                'tableableType' => $entry->tableable_type,
                'tableableId' => $entry->tableable_id ? (int) $entry->tableable_id : null,
                'transactionDate' => $newDate,
            ];
        }

        app(CustomerDebtSnapshotInvalidator::class)->invalidateMany($contributions);
    }

    public function deleting(Transaction $transaction): void
    {
        $transactionDate = $transaction->getAttribute('transaction_date');
        $contributions = $transaction->entries()->get()->map(fn ($entry): array => [
            'accountId' => (int) $entry->account_id,
            'tableableType' => $entry->tableable_type,
            'tableableId' => $entry->tableable_id ? (int) $entry->tableable_id : null,
            'transactionDate' => $transactionDate,
        ])->all();

        app(CustomerDebtSnapshotInvalidator::class)->invalidateMany($contributions);
    }
}
