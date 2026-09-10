<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

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

    protected static function booted(): void
    {
        static::saving(function (ProductStorage $inventory): void {
            if (! Schema::hasColumn('products', 'branch_id') || ! Schema::hasColumn('storages', 'branch_id')) {
                return;
            }

            $productBranchId = Product::query()->whereKey($inventory->product_id)->value('branch_id');
            $storageBranchId = Storage::query()->whereKey($inventory->storage_id)->value('branch_id');

            if ($productBranchId === null || $storageBranchId === null || (int) $productBranchId !== (int) $storageBranchId) {
                throw ValidationException::withMessages([
                    'product_id' => 'Sản phẩm và kho phải thuộc cùng một chi nhánh.',
                ]);
            }
        });
    }

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
