<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'permissions';

    protected $fillable = [
        'module',
        'permission_key',
        'description',
    ];

    /**
     * Một Permission có nhiều bản ghi trong bảng role_permission
     */
    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class, 'permission_id');
    }
}