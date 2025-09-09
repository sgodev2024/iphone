<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'address',
        'email',
        'tax_number',
        'bank_account',
        'bank_id',
        'city_id',
        'note',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function supplier()
    {
        return $this->hasMany(Supplier::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }

    public function hasRepresentative()
    {
        return $this->supplier()->exists();
    }

    public function product()
    {
        return $this->belongsToMany(Product::class, 'company_product');
    }
}
