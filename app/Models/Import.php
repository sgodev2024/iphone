<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
