<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\UserInfo;
use App\Models\Storage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'manager_id',
        'name',
        'phone',
        'email',
        'password',
        'status',
        'role_id',
        'branch_id',
        'address',
        'company_name',
        'tax_code',
        'store_name',
        'storage_id',
        'img_url'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = ['user_info'];

    // Accessor for user info
    public function getUserInfoAttribute()
    {
        if ($this->relationLoaded('userInfo')) {
            return $this->getRelation('userInfo');
        }

        return $this->userInfo()->first();
    }

    public function getImgUrlAttribute($value)
    {
        return $value ?: optional($this->user_info)->img_url;
    }

    public function userInfo()
    {
        return $this->hasOne(UserInfo::class, 'user_id');
    }

    // Relationship with City
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    // Relationship with Field
    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    // Relationship with Config
    public function config()
    {
        return $this->hasOne(Config::class);
    }

    // Relationship with Storage
    public function storage()
    {
        return $this->belongsTo(Storage::class);
    }
    public function role()
    {
        return $this->belongsTo(Roles::class, 'role_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function owner(): User
    {
        if ($this->manager_id === null) {
            return $this;
        }
    
        return $this->manager()->first() ?? $this;
    }
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
    public function employees()
    {
        return $this->hasMany(User::class, 'manager_id');
    }
    public function ownerId(): int
    {
        return $this->owner()->id;
    }

    public function roleKey(): ?string
    {
        return $this->role?->normalizedName();
    }

    public function hasFullAccess(): bool
    {
        return $this->role?->grantsAllPermissions() ?? false;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->hasFullAccess()) {
            return true;
        }

        return $this->role?->hasPermission($permission) ?? false;
    }

    public function matchesRoleRequirement(string|int $requiredRole): bool
    {
        if ($this->hasFullAccess()) {
            return true;
        }

        $requiredRole = trim((string) $requiredRole);

        if ($requiredRole === '') {
            return false;
        }

        if (ctype_digit($requiredRole)) {
            return (int) $this->role_id === (int) $requiredRole;
        }

        return $this->roleKey() === strtolower($requiredRole);
    }
    public function transaction()
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }
}
