<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReturnDetail extends Model
{
    protected $fillable = [
        'order_return_id',
        'order_detail_id',
        'product_id',
        'product_imei_id',
        'storage_id',
        'quantity',
        'original_unit_price',
        'gross_amount',
        'discount_amount',
        'return_amount',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'original_unit_price' => 'integer',
        'gross_amount' => 'integer',
        'discount_amount' => 'integer',
        'return_amount' => 'integer',
    ];

    public function orderReturn(): BelongsTo
    {
        return $this->belongsTo(OrderReturn::class);
    }

    public function orderDetail(): BelongsTo
    {
        return $this->belongsTo(OrderDetail::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productImei(): BelongsTo
    {
        return $this->belongsTo(ProductImei::class);
    }

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }
}