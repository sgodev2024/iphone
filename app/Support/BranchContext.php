<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
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

    public function scope(Builder|QueryBuilder $query, User $user, string $column = 'branch_id'): Builder|QueryBuilder
    {
        if (! $this->hasBranchColumn($query, $column) || $this->isGlobal($user)) {
            return $query;
        }

        return $query->where($column, $this->branchId($user));
    }

    public function resolveWriteBranch(User $user, ?int $requestedBranchId = null): int
    {
        if (! Schema::hasTable('branches')) {
            return (int) ($user->branch_id ?? 0);
        }

        if (! $this->isGlobal($user)) {
            return $this->branchId($user);
        }

        if ($requestedBranchId === null
            || ! Branch::query()
                ->whereKey($requestedBranchId)
                ->where('user_id', $user->id)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'branch_id' => ['Cửa hàng không hợp lệ hoặc không thuộc phạm vi quản trị.'],
            ]);
        }

        return $requestedBranchId;
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
        if (! Schema::hasColumn('storages', 'branch_id')) {
            return $query;
        }

        if ($this->isGlobal($user)) {
            return $query;
        }

        if (! Schema::hasTable('branches')) {
            $this->branchId($user);

            return $query->visibleTo($user);
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
        if (! Schema::hasColumn('storages', 'branch_id')) {
            return $query;
        }

        if ($this->isGlobal($user)) {
            return $query;
        }

        if (! Schema::hasTable('branches')) {
            $this->branchId($user);

            return $query->whereHas(
                $relation,
                fn (Builder $storageQuery) => $storageQuery->visibleTo($user)
            );
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
        if (! Schema::hasColumn('storages', 'branch_id')) {
            return;
        }

        if ($this->isGlobal($user)) {
            return;
        }

        if (! Schema::hasTable('branches')) {
            $this->branchId($user);
            abort_unless(
                Storage::query()->visibleTo($user)->whereKey($storage->id)->exists(),
                Response::HTTP_NOT_FOUND
            );

            return;
        }

        $this->authorize($user, $storage->branch_id === null ? null : (int) $storage->branch_id);

        if ($user->isStaff()
            && ($user->storage_id === null || (int) $storage->id !== (int) $user->storage_id)
        ) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }

    private function hasBranchColumn(Builder|QueryBuilder $query, string $column): bool
    {
        $table = $query instanceof Builder
            ? $query->getModel()->getTable()
            : (string) $query->from;
        $table = preg_split('/\s+(?:as\s+)?/i', trim($table))[0] ?? $table;
        $column = str_contains($column, '.')
            ? substr($column, strrpos($column, '.') + 1)
            : $column;

        $connection = $query instanceof Builder
            ? $query->getQuery()->getConnection()
            : $query->getConnection();
        $driver = $connection->getDriverName();
        $pdo = $connection->getPdo();

        if ($driver === 'sqlite'
            && preg_match('/^[A-Za-z0-9_]+$/', $table) === 1
        ) {
            $statement = $pdo->query('PRAGMA table_info("'.$table.'")');

            foreach ($statement?->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $definition) {
                if (($definition['name'] ?? null) === $column) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'mysql') {
            $statement = $pdo->prepare(
                'SELECT 1 FROM information_schema.columns '
                .'WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1'
            );
            $statement->execute([$table, $column]);

            return $statement->fetchColumn() !== false;
        }

        return Schema::connection($connection->getName())->hasColumn($table, $column);
    }

}
