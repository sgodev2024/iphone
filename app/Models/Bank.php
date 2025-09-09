<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;
    protected $table = 'banks';

    protected $fillable = ['name', 'code', 'bin', 'shortName'];

    public function config()
    {
        return $this->hasOne(Config::class);
    }

    public function companies()
    {
        return $this->hasMany(Company::class, 'bank_id');
    }

    public function superAdmin()
    {
        return $this->hasOne(SuperAdmin::class, 'bank_id');
    }
}
