<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    protected $table = 'suppliers';
    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'company_id',
    ];

    protected function company()
    {
        return $this->belongsTo(Company::class);
    }
}
