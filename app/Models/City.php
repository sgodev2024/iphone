<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;
    protected $table = 'city';
    protected $fillable = [
        'country_id',
        'name',
    ];

    public function district(){
        return $this->hasMany(Districts::class);
    }

    public function user(){
        return $this->hasOne(User::class);
    }

    public function company(){
        return $this->hasMany(Company::class, 'city_id');
    }
}
