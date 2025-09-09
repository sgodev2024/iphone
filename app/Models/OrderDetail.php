<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;
    protected $table = 'order_details';
    protected $fillable = ['order_id', 'storage_id', 'product_id', 'p_name', 'p_price', 'p_quantity'];

    protected $appends = ['product'];

    public function getproductAttribute()
    {
        return Product::where('id', $this->attributes['product_id'])->first();
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
