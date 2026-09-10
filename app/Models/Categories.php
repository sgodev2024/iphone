<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class Categories extends Model
{
    use HasFactory;
    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'status'
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    protected $casts = [
        'status' => 'boolean'
    ];

    protected static function booted(): void
    {
        static::saving(function (Categories $category): void {
            if (Schema::hasColumn('categories', 'branch_id')
                && $category->exists
                && $category->isDirty('branch_id')
            ) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Không thể chuyển danh mục sang chi nhánh khác.',
                ]);
            }
        });
    }
}
