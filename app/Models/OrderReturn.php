<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderReturn extends Model
{
    protected $fillable = [
        'code',
        'original_order_id',
        'exchange_order_id',
        'user_id',
        'branch_id',
        'client_id',
        'created_by',
        'return_amount',
        'exchange_amount',
        'fee_amount',
        'refund_amount',
        'additional_payment',
        'status',
        'note',
    ];

    protected $casts = [
        'return_amount' => 'integer',
        'exchange_amount' => 'integer',
        'fee_amount' => 'integer',
        'refund_amount' => 'integer',
        'additional_payment' => 'integer',
    ];

    public function originalOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'original_order_id');
    }

    public function exchangeOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'exchange_order_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderReturnDetail::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}