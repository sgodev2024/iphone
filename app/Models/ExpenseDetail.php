<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseDetail extends Model
{
    use HasFactory;
    protected $table = 'expense_detail';

    protected $fillable = [
        'expense_id',
        'content',
        'amount',
        'date',
    ];
}
