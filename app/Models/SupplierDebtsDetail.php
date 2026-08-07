<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierDebtsDetail extends Model
{
    use HasFactory;
    protected $table = 'supplier_debts_detail';

    protected $fillable = [
        'supplier_debts_id',
        'content',
        'amount',
    ];
}
