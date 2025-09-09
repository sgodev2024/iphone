<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Branch extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'address',
        'phone',
        'email',
        'manager_name',
        'status'
    ];
    protected function statusText(): Attribute
    {
        return Attribute::get(fn() => $this->status == 1 ? 'Hoạt động' : 'Không hoạt động');
    }
}
