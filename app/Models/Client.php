<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
    protected $table = 'clients'; // Tên bảng trong cơ sở dữ liệu
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'zip_code',
        'address',
        'dob',
        'email',
        'gender',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->code = generateCode('clients', 'KH');
        });
    }
}
