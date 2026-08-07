<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use HasFactory;
    protected $table = 'wards';

    protected $fillable = [
        'district_id',
        'name',
        'id',
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }
}
