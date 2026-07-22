<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImei extends Model
{
    use HasFactory;

    public const STATUS_IN_STOCK = 'in_stock';

    public const STATUS_SOLD = 'sold';

    protected $fillable = [
        'product_id',
        'import_detail_id',
        'imei',
        'status',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function importDetail(): BelongsTo
    {
        return $this->belongsTo(ImportDetail::class);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_IN_STOCK);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_IN_STOCK => 'Đang tồn kho',
            self::STATUS_SOLD => 'Đã bán',
            default => $this->status,
        };
    }
}
