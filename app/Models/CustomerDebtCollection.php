<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class CustomerDebtCollection extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'owner_id',
        'branch_id',
        'client_id',
        'collection_number',
        'collection_date',
        'payment_method',
        'money_account_id',
        'total_amount',
        'note',
        'attachment',
        'status',
        'idempotency_key',
        'idempotency_hash',
        'created_by',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'collection_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $collection): void {
            if ($collection->getOriginal('status') === self::STATUS_COMPLETED) {
                throw new LogicException('Completed customer debt collections are immutable.');
            }
        });

        static::deleting(function (self $collection): void {
            if ($collection->status === self::STATUS_COMPLETED) {
                throw new LogicException('Completed customer debt collections cannot be deleted.');
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function moneyAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'money_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CustomerDebtCollectionAllocation::class, 'collection_id')
            ->orderBy('allocation_sequence');
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'collection_id');
    }
}
