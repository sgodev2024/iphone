<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

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

    public function scopeOfBranch(Builder $query, int $branchId): Builder
    {
        return $query
            ->join('storages', 'storages.id', '=', 'product_storage.storage_id')
            ->where('storages.branch_id', $branchId);
    }
}