<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZaloOa extends Model
{
    use HasFactory;

    protected $table = 'sgo_zalo_oas';

    protected $fillable = [
        'name',
        'oa_id',
        'access_token',
        'refresh_token',
        'package_valid_through_date',
        'is_active'
    ];

    // Define a relationship with ZnsMessage
    public function messages()
    {
        return $this->hasMany(ZnsMessage::class, 'oa_id');
    }
}
