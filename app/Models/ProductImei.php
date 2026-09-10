<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ProductImei extends Model
{
    use SoftDeletes, HasFactory;

    public const STATUS_IN_STOCK = 'in_stock';

    public const STATUS_SOLD = 'sold';

    public const IMEI_MAX_LENGTH = 50;

    protected $fillable = [
        'product_id',
        'import_detail_id',
        'imei',
        'barcode',
        'status',
        'printed_at',
        'print_count',
        'deleted_by',
        'delete_reason',
        'storage_id',
    ];
    protected $casts = [
        'printed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (ProductImei $imei): void {
            if (! Schema::hasColumn('products', 'branch_id') || ! Schema::hasColumn('storages', 'branch_id')) {
                return;
            }

            $productBranchId = Product::query()->whereKey($imei->product_id)->value('branch_id');
            $storageBranchId = Storage::query()->whereKey($imei->storage_id)->value('branch_id');

            if ($productBranchId === null || $storageBranchId === null || (int) $productBranchId !== (int) $storageBranchId) {
                throw ValidationException::withMessages([
                    'storage_id' => 'IMEI, sản phẩm và kho phải thuộc cùng một chi nhánh.',
                ]);
            }

            if ($imei->import_detail_id === null) {
                return;
            }

            $importStorageBranchId = ImportDetail::query()
                ->join('import_coupon', 'import_coupon.id', '=', 'import_detail.import_id')
                ->join('storages', 'storages.id', '=', 'import_coupon.storage_id')
                ->where('import_detail.id', $imei->import_detail_id)
                ->value('storages.branch_id');

            if ($importStorageBranchId === null || (int) $importStorageBranchId !== (int) $storageBranchId) {
                throw ValidationException::withMessages([
                    'import_detail_id' => 'IMEI và phiếu nhập phải thuộc cùng một chi nhánh.',
                ]);
            }
        });
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function importDetail(): BelongsTo
    {
        return $this->belongsTo(ImportDetail::class);
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_IN_STOCK);
    }

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
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
