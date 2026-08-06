<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasFactory;

    protected $table = 'role_permission';

    protected $fillable = [
        'guard_name',
        'role_id',
        'permission_id',
    ];

    /**
     * Thuộc về một Role
     */
    public function role()
    {
        return $this->belongsTo(Roles::class, 'role_id');
    }

    /**
     * Thuộc về một Permission
     */
    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }
}