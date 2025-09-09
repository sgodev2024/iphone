<?php

namespace App\Models;

use App\Models\Roles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasFactory;
    protected $table = 'role_permission';

    protected $fillable = [
        "guard_name",
        "role_id",
    ];
    public function role()
    {
        return $this->belongsTo(Roles::class);
    }
}
