<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Transaction extends Model
{
    public const STATUS_PAID = 'paid';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'transaction_date',
        'description',
        'reference_number',
        'type',
        'document_type',
        'attachment',
        'created_by',
        'status',
        'idempotency_key',
        'idempotency_hash',
        'collection_id',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $transaction): void {
            if ($transaction->getOriginal('collection_id') !== null) {
                throw new LogicException('Customer debt collection transactions are immutable.');
            }
        });

        static::deleting(function (self $transaction): void {
            if ($transaction->collection_id !== null) {
                throw new LogicException('Customer debt collection transactions cannot be deleted.');
            }
        });
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }

    public function customerDebtCollection(): BelongsTo
    {
        return $this->belongsTo(CustomerDebtCollection::class, 'collection_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    /**
     * Lấy entry tài khoản tiền
     */
    public function getMainEntry(array $moneyAccountIds)
    {
        return $this->entries->firstWhere(
            fn($entry) =>
            in_array($entry->account_id, $moneyAccountIds)
        );
    }

    /**
     * Lấy entry tài khoản công nợ đối ứng
     */
    public function getContraEntry(array $moneyAccountIds)
    {
        return $this->entries->firstWhere(
            fn($entry) =>
            !in_array($entry->account_id, $moneyAccountIds)
        );
    }

    protected $casts = [
        'transaction_date' => 'date',
    ];
}
