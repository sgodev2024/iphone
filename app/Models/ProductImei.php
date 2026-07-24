<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductImei extends Model
{
    use SoftDeletes, HasFactory;

    public const STATUS_IN_STOCK = 'in_stock';

    public const STATUS_SOLD = 'sold';

    public const MAX_IMPORT_QUANTITY = 35;

    protected $fillable = [
        'product_id',
        'import_detail_id',
        'imei',
        'status',
        'deleted_by',
        'delete_reason',
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
