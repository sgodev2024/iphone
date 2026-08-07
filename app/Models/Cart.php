<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;
    protected $table = 'carts';

    protected $fillable = [
        "product_id",
        "user_id",
        "amount",
        'price'
    ];

    protected $appends = ['product'];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function product()
    {
        return $this->belongsToMany(Product::class, 'product_id');
    }
    public function getProductAttribute()
    {
        return Product::where('id', $this->attributes['product_id'])->first();
    }

}
