<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Storage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'name',
        'location',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_storage')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function productStorages(): HasMany
    {
        return $this->hasMany(ProductStorage::class);
    }

    public function productImeis(): HasMany
    {
        return $this->hasMany(ProductImei::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeOfBranch(Builder $query, ?int $branchId): Builder
    {
        if ($branchId === null) {
            return $query;
        }
        return $query->where('branch_id', $branchId);
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdministrator()) {
            return $query;
        }

        if ($user->isAdminStore()) {
            return $user->branch_id === null
                ? $query->whereRaw('1 = 0')
                : $query->where('branch_id', (int) $user->branch_id);
        }

        if ($user->isStaff()) {
            return $user->branch_id === null || $user->storage_id === null
                ? $query->whereRaw('1 = 0')
                : $query
                    ->where('branch_id', (int) $user->branch_id)
                    ->whereKey((int) $user->storage_id);
        }

        $ownerIds = collect([$user->id, $user->manager_id])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $query->where(function (Builder $query) use ($ownerIds, $user) {
            if ($ownerIds !== []) {
                $query->whereIn('user_id', $ownerIds);
            }

            if ($user->storage_id) {
                $query->orWhere('id', (int) $user->storage_id);
            }
        });
    }
}
