<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierDebtYearlySnapshot extends Model
{
    protected $fillable = [
        'owner_id',
        'company_id',
        'fiscal_year',
        'opening_debit',
        'opening_credit',
        'source_through_date',
        'source_version',
        'calculated_at',
    ];

    protected $casts = [
        'owner_id' => 'integer',
        'company_id' => 'integer',
        'fiscal_year' => 'integer',
        'opening_debit' => 'decimal:2',
        'opening_credit' => 'decimal:2',
        'source_version' => 'integer',
        'source_through_date' => 'date',
        'calculated_at' => 'datetime',
    ];
}
