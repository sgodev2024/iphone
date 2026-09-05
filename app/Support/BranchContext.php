<?php

namespace App\Support;

use App\Models\Storage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;

final class BranchContext
{
    public function isGlobal(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function branchId(User $user): int
    {
        if ($user->branch_id === null) {
            abort(
                Response::HTTP_FORBIDDEN,
                'Tài khoản chưa được gán cửa hàng nên không thể thực hiện nghiệp vụ này.'
            );
        }

        return (int) $user->branch_id;
    }

    public function scope(Builder $query, User $user, string $column = 'branch_id'): Builder
    {
        if ($this->isGlobal($user)) {
            return $query;
        }

        return $query->where($column, $this->branchId($user));
    }

    public function authorize(User $user, ?int $branchId): void
    {
        if ($this->isGlobal($user)) {
            return;
        }

        if ($branchId === null || $branchId !== $this->branchId($user)) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }

    public function scopeStorages(Builder $query, User $user): Builder
    {
        if ($this->isGlobal($user)) {
            return $query;
        }

        $query->where('branch_id', $this->branchId($user));

        if (! $user->isStaff()) {
            return $query;
        }

        return $user->storage_id === null
            ? $query->whereRaw('1 = 0')
            : $query->whereKey((int) $user->storage_id);
    }

    public function scopeThroughStorage(
        Builder $query,
        User $user,
        string $relation = 'storage'
    ): Builder {
        if ($this->isGlobal($user)) {
            return $query;
        }

        $branchId = $this->branchId($user);
        $storageId = $user->isStaff() ? $user->storage_id : null;

        if ($user->isStaff() && $storageId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas($relation, function (Builder $storageQuery) use ($branchId, $storageId): void {
            $storageQuery->where('branch_id', $branchId);

            if ($storageId !== null) {
                $storageQuery->whereKey((int) $storageId);
            }
        });
    }

    public function authorizeStorage(User $user, Storage $storage): void
    {
        if ($this->isGlobal($user)) {
            return;
        }

        $this->authorize($user, $storage->branch_id === null ? null : (int) $storage->branch_id);

        if ($user->isStaff()
            && ($user->storage_id === null || (int) $storage->id !== (int) $user->storage_id)
        ) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }
}
