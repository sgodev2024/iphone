<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    use HasFactory;

    public const ADMINISTRATOR = 'administrator';
    public const ADMIN_STORE = 'admin_store';
    public const STAFF = 'staff';

    public const ADMINISTRATOR_NAMES = [self::ADMINISTRATOR];
    public const ADMIN_STORE_NAMES = [self::ADMIN_STORE];
    public const STAFF_NAMES = [self::STAFF];

    /** Compatibility aliases used only while installations are migrated. */
    private const LEGACY_ADMINISTRATOR_NAMES = ['store'];
    private const LEGACY_ADMIN_STORE_NAMES = ['admin'];

    /** Only the system-wide Administrator may bypass capability checks. */
    public const FULL_ACCESS_ROLE_NAMES = self::ADMINISTRATOR_NAMES;

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
        return $this->isAdministrator();
    }


    public function isAdministrator(): bool
    {
        return in_array($this->normalizedName(), static::administratorNames(), true);
    }

    public function isAdminStore(): bool
    {
        return in_array($this->normalizedName(), static::adminStoreNames(), true);
    }

    public function isStaff(): bool
    {
        return in_array($this->normalizedName(), self::STAFF_NAMES, true);
    }

    public static function administratorIds(): array
    {
        return static::idsForNames(static::administratorNames());
    }

    public static function adminStoreIds(): array
    {
        return static::idsForNames(static::adminStoreNames());
    }

    public static function staffIds(): array
    {
        return static::idsForNames(self::STAFF_NAMES);
    }

    public static function administratorId(): int
    {
        return static::idForNames(static::administratorNames());
    }

    public static function adminStoreId(): int
    {
        return static::idForNames(static::adminStoreNames());
    }

    public static function staffId(): int
    {
        return static::idForNames(self::STAFF_NAMES);
    }


    public static function administratorNames(): array
    {
        return [...self::ADMINISTRATOR_NAMES, ...self::LEGACY_ADMINISTRATOR_NAMES];
    }

    public static function adminStoreNames(): array
    {
        return [...self::ADMIN_STORE_NAMES, ...self::LEGACY_ADMIN_STORE_NAMES];
    }
    private static function idForNames(array $names): int
    {
        foreach ($names as $name) {
            $roleId = static::query()->where('name', $name)->value('id');

            if ($roleId !== null) {
                return (int) $roleId;
            }
        }

        return (int) static::query()->whereIn('name', $names)->firstOrFail()->getKey();
    }

    private static function idsForNames(array $names): array
    {
        return static::query()
            ->whereIn('name', $names)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()
            ->where('permission_key', $permission)
            ->exists();
    }
}
