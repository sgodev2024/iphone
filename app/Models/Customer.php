<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'birthday',
        'gender',
        'customer_type',
        'address',
        'company_name',
        'company_tax_code',
        'company_address',
        'citizen_id',
        'note'
    ];

    protected $casts = [
        'birthday' => 'date'
    ];
}
