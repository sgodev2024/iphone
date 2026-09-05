<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierDebtSnapshotState extends Model
{
    protected $fillable = [
        'owner_id',
        'branch_id',
        'company_id',
        'ledger_version',
        'dirty_from_year',
    ];

    protected $casts = [
        'owner_id' => 'integer',
        'branch_id' => 'integer',
        'company_id' => 'integer',
        'ledger_version' => 'integer',
        'dirty_from_year' => 'integer',
    ];
}
