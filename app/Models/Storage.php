<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Storage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'location',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_storage')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
