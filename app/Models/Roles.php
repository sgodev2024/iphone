<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    use HasFactory;

    /**
     * Full-access roles defined by DatabaseSeeder. Authorization uses names,
     * not auto-increment IDs, so it remains stable across environments.
     */
    public const FULL_ACCESS_ROLE_NAMES = [
        'store',
        'admin',
    ];

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Một Role có nhiều bản ghi trong role_permission
     */
    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class, 'role_id');
    }

    // Giữ nguyên nếu dự án của bạn đang dùng
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permission',
            'role_id',
            'permission_id'
        );}

    public function normalizedName(): string
    {
        return strtolower(trim((string) $this->name));
    }

    public function grantsAllPermissions(): bool
    {
        return in_array($this->normalizedName(), self::FULL_ACCESS_ROLE_NAMES, true);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()
            ->where('permission_key', $permission)
            ->exists();
    }
}
