<?php

namespace App\Models;

use App\Models\Cart;
use App\Models\ProductImages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'brand_id',
        'code',
        'name',
        'price',
        'price_buy',
        'thumbnail',
        'product_unit',
        'quantity',
        'description',
        'is_featured',
        'status'
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'status' => 'boolean',
    ];

    public function getImagesAttribute()
    {
        return ProductImages::where('product_id', $this->attributes['id'])->get();
    }
    public function getCategoryAttribute()
    {
        return Categories::where('id', $this->attributes['category_id'])->first();
    }
    public function getBrandsAttribute()
    {
        return Brand::where('id', $this->attributes['brands_id'])->first();
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
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

    public function storages()
    {
        return $this->belongsToMany(Storage::class, 'product_storage')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
