<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    use HasFactory;
    protected $table = 'import';
    protected $fillable = [
        'product_id',
        'quantity',
        'price',
        'total',
    ];

    protected $appends = ['product'];
    public function getProductAttribute(){
        return Product::where('id',$this->attributes['product_id'])->first();
    }

}
