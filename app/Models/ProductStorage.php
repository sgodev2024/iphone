<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStorage extends Model
{
    use HasFactory;

    protected $table = 'product_storage';

    protected $fillable = [
        'product_id',
        'storage_id',
        'quantity',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function storage()
    {
        return $this->belongsTo(Storage::class, 'storage_id');
    }
}
