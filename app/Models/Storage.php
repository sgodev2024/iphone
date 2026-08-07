<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Storage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'location',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_storage')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function productStorages(): HasMany
    {
        return $this->hasMany(ProductStorage::class);
    }

    public function productImeis(): HasMany
{
    return $this->hasMany(ProductImei::class);
}
}