<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    use HasFactory;

    protected $table = 'fields';
    protected $fillable = [
        'name', 'short_name'
    ];

    public function user()
    {
        return  $this->hasOne(User::class);
    }
}
