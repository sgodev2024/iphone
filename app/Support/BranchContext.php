<?php

namespace App\Support;

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
}
