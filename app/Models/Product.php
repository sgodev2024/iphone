<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class Product extends Model
{
    use HasFactory;

    public const INVENTORY_TRACKING_IMEI = 'imei';

    public const INVENTORY_TRACKING_QUANTITY = 'quantity';

    public const INVENTORY_TRACKING_OPTIONS = [
        self::INVENTORY_TRACKING_IMEI,
        self::INVENTORY_TRACKING_QUANTITY,
    ];

    public const DEFAULT_THUMBNAIL = 'images/default-product.png';

    protected $fillable = [
        'branch_id',
        'user_id',
        'category_id',
        'brands_id',
        'code',
        'barcode',
        'name',
        'price',
        'price_buy',
        'thumbnail',
        'product_unit',
        'quantity',
        'inventory_tracking',
        'description',
        'is_featured',
        'status',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'quantity' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            if (! Schema::hasColumn('products', 'branch_id') || ! Schema::hasColumn('categories', 'branch_id')) {
                return;
            }

            if ($product->exists && $product->isDirty('branch_id')) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Không thể chuyển sản phẩm sang chi nhánh khác.',
                ]);
            }

            $categoryBranchId = Categories::query()
                ->whereKey($product->category_id)
                ->value('branch_id');

            if ($product->branch_id === null
                || $categoryBranchId === null
                || (int) $product->branch_id !== (int) $categoryBranchId
            ) {
                throw ValidationException::withMessages([
                    'category_id' => 'Sản phẩm và danh mục phải thuộc cùng một chi nhánh.',
                ]);
            }

            if ($product->brands_id !== null && Schema::hasColumn('brands', 'branch_id')) {
                $brandBranchId = Brand::query()
                    ->whereKey($product->brands_id)
                    ->value('branch_id');

                if ($brandBranchId === null
                    || (int) $product->branch_id !== (int) $brandBranchId
                ) {
                    throw ValidationException::withMessages([
                        'brands_id' => 'Sản phẩm và thương hiệu phải thuộc cùng một cửa hàng.',
                    ]);
                }
            }
        });
    }

    public function getImagesAttribute()
    {
        return ProductImages::where('product_id', $this->attributes['id'])->get();
    }

    public function getThumbnailUrlAttribute(): string
    {
        return productImage($this->thumbnail ?? null);
    }

    public function getCategoryAttribute()
    {
        return Categories::where('id', $this->attributes['category_id'])->first();
    }

    public function getBrandsAttribute()
    {
        return Brand::where('id', $this->attributes['brands_id'] ?? null)->first();
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brands_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function carts()
    {
        return $this->belongsToMany(Cart::class);
    }

    public function category()
    {
        return $this->belongsTo(Categories::class);
    }

    public function company()
    {
        return $this->belongsToMany(Company::class, 'company_product');
    }

    public function productImages()
    {
        return $this->hasMany(ProductImages::class);
    }

    public function imeis(): HasMany
    {
        return $this->hasMany(ProductImei::class);
    }

    public function importDetails(): HasMany
    {
        return $this->hasMany(ImportDetail::class);
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function productStorages(): HasMany
    {
        return $this->hasMany(ProductStorage::class);
    }

    public function storages()
    {
        return $this->belongsToMany(Storage::class, 'product_storage')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function isImeiTracked(): bool
    {
        return $this->inventory_tracking === self::INVENTORY_TRACKING_IMEI;
    }

    public function isQuantityTracked(): bool
    {
        return $this->inventory_tracking === self::INVENTORY_TRACKING_QUANTITY;
    }

    public function getInventoryTrackingLabelAttribute(): string
    {
        return $this->isImeiTracked() ? 'IMEI' : 'Sản phẩm thường';
    }

    public function hasInventoryTrackingActivity(): bool
    {
        return $this->imeis()->exists()
            || $this->importDetails()->exists()
            || $this->orderDetails()->exists()
            || $this->productStorages()->where('quantity', '>', 0)->exists();
    }

    public function canChangeInventoryTracking(): bool
    {
        if (! $this->exists) {
            return true;
        }

        return ! $this->hasInventoryTrackingActivity();
    }
}
