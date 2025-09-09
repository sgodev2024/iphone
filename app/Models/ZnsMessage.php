<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZnsMessage extends Model
{
    use HasFactory;

    protected $table = 'sgo_zns_messages';

    protected $fillable = [
        'name',
        'phone',
        'sent_at',
        'status',
        'note',
        'template_id',
        'oa_id'  // Ensure this is included if it's used in relationships
    ];

    // Define a relationship with OaTemplate
    public function template()
    {
        return $this->belongsTo(OaTemplate::class, 'template_id');
    }

    // Define a relationship with ZaloOa
    public function zaloOa()
    {
        return $this->belongsTo(ZaloOa::class, 'oa_id');
    }

}
