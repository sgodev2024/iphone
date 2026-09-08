<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    use HasFactory;

    public const ADMINISTRATOR_ID = 1;

    public const ADMIN_STORE_ID = 2;

    public const STAFF_ID = 3;

    public const CANONICAL_IDS = [
        self::ADMINISTRATOR_ID,
        self::ADMIN_STORE_ID,
        self::STAFF_ID,
    ];

    public const ASSIGNABLE_PERMISSION_ROLE_IDS = [
        self::ADMIN_STORE_ID,
        self::STAFF_ID,
    ];

    public const ADMINISTRATOR = 'administrator';

    public const ADMIN_STORE = 'admin_store';

    public const STAFF = 'staff';

    public const ADMINISTRATOR_NAMES = [self::ADMINISTRATOR];

    public const ADMIN_STORE_NAMES = [self::ADMIN_STORE];

    public const STAFF_NAMES = [self::STAFF];

    /** Compatibility aliases for reading legacy data; never use for authorization. */
    private const LEGACY_ADMINISTRATOR_NAMES = ['store'];

    private const LEGACY_ADMIN_STORE_NAMES = ['admin'];

    public const FULL_ACCESS_ROLE_NAMES = self::ADMINISTRATOR_NAMES;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'description',
    ];

    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class, 'role_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    /** @deprecated Use users(). */
    public function user()
    {
        return $this->users();
    }

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permission',
            'role_id',
            'permission_id'
        )->wherePivot('guard_name', 'web');
    }

    public function normalizedName(): string
    {
        return strtolower(trim((string) $this->name));
    }

    public function grantsAllPermissions(): bool
    {
        return $this->isAdministrator();
    }

    public function isAdministrator(): bool
    {
        return (int) $this->getKey() === self::ADMINISTRATOR_ID;
    }

    public function isAdminStore(): bool
    {
        return (int) $this->getKey() === self::ADMIN_STORE_ID;
    }

    public function isStaff(): bool
    {
        return (int) $this->getKey() === self::STAFF_ID;
    }

    public static function administratorIds(): array
    {
        return [self::ADMINISTRATOR_ID];
    }

    public static function adminStoreIds(): array
    {
        return [self::ADMIN_STORE_ID];
    }

    public static function staffIds(): array
    {
        return [self::STAFF_ID];
    }

    public static function administratorId(): int
    {
        return self::ADMINISTRATOR_ID;
    }

    public static function adminStoreId(): int
    {
        return self::ADMIN_STORE_ID;
    }

    public static function staffId(): int
    {
        return self::STAFF_ID;
    }

    public static function administratorNames(): array
    {
        return [...self::ADMINISTRATOR_NAMES, ...self::LEGACY_ADMINISTRATOR_NAMES];
    }

    public static function adminStoreNames(): array
    {
        return [...self::ADMIN_STORE_NAMES, ...self::LEGACY_ADMIN_STORE_NAMES];
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()
            ->where('permission_key', $permission)
            ->exists();
    }
}
