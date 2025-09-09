<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OaTemplate extends Model
{
    use HasFactory;

    protected $table = 'sgo_oa_template';

    protected $fillable = [
        'oa_id',
        'template_id',
        'template_name',
        'price',
    ];

    public function zaloOa()
    {
        return $this->belongsTo(ZaloOa::class, 'oa_id');
    }

    /**
     * Mối quan hệ với bảng sgo_zns_messages.
     */
    public function znsMessages()
    {
        return $this->hasMany(ZnsMessage::class);
    }

    public function campaigns()
    {
        return $this->hasOne(Campaign::class, 'template_id');
    }
}
