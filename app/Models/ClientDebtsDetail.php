<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientDebtsDetail extends Model
{
    use HasFactory;
    protected $table = 'customer_debts_detail';

    protected $fillable = [
        'customer_debts_id',
        'content',
        'amount',
    ];
}
