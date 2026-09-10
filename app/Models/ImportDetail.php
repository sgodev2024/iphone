<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ImportDetail extends Model
{
    use HasFactory;

    protected $table = 'import_detail';

    protected $fillable = [
        'import_id',
        'product_id',
        'quantity',
        'price',
        'old_price',
    ];

    protected static function booted(): void
    {
        static::saving(function (ImportDetail $detail): void {
            if (! Schema::hasColumn('products', 'branch_id') || ! Schema::hasColumn('storages', 'branch_id')) {
                return;
            }

            $productBranchId = Product::query()->whereKey($detail->product_id)->value('branch_id');
            $importBranchId = ImportCoupon::query()
                ->join('storages', 'storages.id', '=', 'import_coupon.storage_id')
                ->where('import_coupon.id', $detail->import_id)
                ->value('storages.branch_id');

            if ($productBranchId === null || $importBranchId === null || (int) $productBranchId !== (int) $importBranchId) {
                throw ValidationException::withMessages([
                    'product_id' => 'Sản phẩm và phiếu nhập phải thuộc cùng một chi nhánh.',
                ]);
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function import()
    {
        return $this->belongsTo(ImportCoupon::class, 'import_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function imeis(): HasMany
    {
        return $this->hasMany(ProductImei::class);
    }
}
