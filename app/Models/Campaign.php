<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $table = 'sgo_campaigns';

    protected $fillable = [
        'name',
        'template_id',
        'sent_time',
        'status',
        'sent_date'
    ];


    public function details()
    {
        return $this->hasMany(CampaignDetail::class);
    }
    public function template()
    {
        return $this->belongsTo(OaTemplate::class);
    }
}
