<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Services\Accounting\CustomerDebtSnapshotInvalidator;

class TransactionEntryObserver
{
    public function created(TransactionEntry $entry): void
    {
        app(CustomerDebtSnapshotInvalidator::class)->invalidateMany([
            $this->contribution($entry->getAttributes()),
        ]);
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

        app(CustomerDebtSnapshotInvalidator::class)->invalidateMany([
            $this->contribution($entry->getOriginal()),
            $this->contribution($entry->getAttributes()),
        ]);
    }

    public function deleted(TransactionEntry $entry): void
    {
        app(CustomerDebtSnapshotInvalidator::class)->invalidateMany([
            $this->contribution($entry->getOriginal()),
        ]);
    }

    private function contribution(array $attributes): array
    {
        $transactionId = (int) ($attributes['transaction_id'] ?? 0);
        $transactionDate = Transaction::query()
            ->whereKey($transactionId)
            ->value('transaction_date');

        return [
            'accountId' => (int) ($attributes['account_id'] ?? 0),
            'tableableType' => $attributes['tableable_type'] ?? null,
            'tableableId' => isset($attributes['tableable_id']) ? (int) $attributes['tableable_id'] : null,
            'transactionDate' => $transactionDate,
        ];
    }
}
