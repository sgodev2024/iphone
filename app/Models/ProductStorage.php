<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStorage extends Model
{
    protected $table = 'product_storage';

    protected $fillable = [
        'product_id',
        'storage_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }
}