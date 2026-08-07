<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInfo extends Model
{
    use HasFactory;
    protected $table = 'user_info';
    protected $fillable = [
        "img_url",
        "idnumber",
        "bank_name",
        "bank",
        "branch",
        'user_id',
        'citizen_id_number',
        'front_image',
        'back_image'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
