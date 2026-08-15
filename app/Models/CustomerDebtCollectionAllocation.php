<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CustomerDebtCollectionAllocation extends Model
{
    protected $fillable = [
        'collection_id',
        'order_id',
        'allocated_amount',
        'allocation_sequence',
        'remaining_after',
        'payment_transaction_id',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'remaining_after' => 'decimal:2',
        'allocation_sequence' => 'integer',
    ];

    protected static function booted(): void
    {
        $guard = function (self $allocation): void {
            $collection = $allocation->relationLoaded('collection')
                ? $allocation->collection
                : $allocation->collection()->first();

            if ($collection?->status === CustomerDebtCollection::STATUS_COMPLETED) {
                throw new LogicException('Allocations of completed customer debt collections are immutable.');
            }
        };

        static::updating($guard);
        static::deleting($guard);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(CustomerDebtCollection::class, 'collection_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'payment_transaction_id');
    }
}
