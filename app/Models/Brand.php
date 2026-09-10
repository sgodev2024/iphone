<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['branch_id', 'name', 'logo', 'description', 'status'];

    protected $casts = [
        'status' => 'boolean'
    ];

    protected static function booted(): void
    {
        static::saving(function (Brand $brand): void {
            if ($brand->exists
                && Schema::hasColumn('brands', 'branch_id')
                && $brand->isDirty('branch_id')
            ) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Không thể chuyển thương hiệu sang cửa hàng khác.',
                ]);
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brands_id');
    }
}
