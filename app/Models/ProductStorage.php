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

    public function scopeOfBranch(Builder $query, ?int $branchId): Builder
    {
        if ($branchId === null) {
            return $query;
        }
    
        return $query->whereHas('storage', function (Builder $query) use ($branchId) {
            $query->where('branch_id', $branchId);
        });
    }
}