<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientGroup extends Model
{
    use HasFactory;
    protected $table = 'client_group';
    protected $fillable = [
        'code',
        'name',
        'description',
    ];

    protected $appends = ['client'];

    public function getClientAttribute(){
        return Client::where('clientgroup_id',$this->attributes['id'])->get();
    }
}
