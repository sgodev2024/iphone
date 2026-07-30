<?php

namespace App\Services;

use App\Models\Storage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class SaleStorageResolver
{
    private const MANAGER_ROLE_IDS = [1, 2];

    private const STAFF_ROLE_ID = 3;

    private const BRANCH_ROLE_ID = 2;

    private const SESSION_KEY_PREFIX = 'sale.storage_id.';

    public function resolveSaleStorageId(User $user, mixed $requestedStorageId = null): int
    {
        if ((int) $user->role_id === self::STAFF_ROLE_ID) {
            if (! $user->storage_id || ! Storage::query()->whereKey($user->storage_id)->exists()) {
                throw ValidationException::withMessages([
                    'storage_id' => 'Nhân viên chưa được gán kho bán hàng.',
                ]);
            }

            return (int) $user->storage_id;
        }

        $storages = $this->managedStorages($user);

        if ($storages->isEmpty()) {
            $this->forgetSelection($user);

            throw ValidationException::withMessages([
                'storage_id' => 'Chưa có kho bán hàng. Vui lòng tạo hoặc phân quyền kho.',
            ]);
        }

        if ($requestedStorageId !== null && $requestedStorageId !== '') {
            $storageId = filter_var($requestedStorageId, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);

            if ($storageId === false || ! $storages->contains('id', (int) $storageId)) {
                throw ValidationException::withMessages([
                    'storage_id' => 'Kho bán hàng đã chọn không thuộc quyền quản lý của tài khoản.',
                ]);
            }

            $this->storeSelection($user, (int) $storageId);

            return (int) $storageId;
        }

        if ($storages->count() === 1) {
            $storageId = (int) $storages->first()->id;
            $this->storeSelection($user, $storageId);

            return $storageId;
        }

        $selectedStorageId = (int) Session::get($this->sessionKey($user), 0);

        if ($selectedStorageId > 0 && $storages->contains('id', $selectedStorageId)) {
            return $selectedStorageId;
        }

        $this->forgetSelection($user);

        throw ValidationException::withMessages([
            'storage_id' => 'Vui lòng chọn kho bán hàng.',
        ]);
    }

    public function selectSaleStorageId(User $user, mixed $requestedStorageId): int
    {
        if ((int) $user->role_id === self::STAFF_ROLE_ID) {
            throw ValidationException::withMessages([
                'storage_id' => 'Nhân viên không được tự thay đổi kho bán hàng.',
            ]);
        }

        return $this->resolveSaleStorageId($user, $requestedStorageId);
    }

    public function saleStorageContext(User $user): array
    {
        if ((int) $user->role_id === self::STAFF_ROLE_ID) {
            $storage = $user->storage_id
                ? Storage::query()->find($user->storage_id)
                : null;

            return [
                'storages' => $storage ? collect([$storage]) : collect(),
                'selectedStorage' => $storage,
                'canSelectStorage' => false,
                'message' => $storage ? null : 'Nhân viên chưa được gán kho bán hàng.',
            ];
        }

        $storages = $this->managedStorages($user);

        if ($storages->isEmpty()) {
            $this->forgetSelection($user);

            return [
                'storages' => $storages,
                'selectedStorage' => null,
                'canSelectStorage' => false,
                'message' => 'Chưa có kho bán hàng. Vui lòng tạo hoặc phân quyền kho.',
            ];
        }

        $selectedStorage = null;

        if ($storages->count() === 1) {
            $selectedStorage = $storages->first();
            $this->storeSelection($user, (int) $selectedStorage->id);
        } else {
            $selectedStorageId = (int) Session::get($this->sessionKey($user), 0);
            $selectedStorage = $storages->firstWhere('id', $selectedStorageId);

            if (! $selectedStorage) {
                $this->forgetSelection($user);
            }
        }

        return [
            'storages' => $storages,
            'selectedStorage' => $selectedStorage,
            'canSelectStorage' => $storages->count() > 1,
            'message' => $selectedStorage ? null : 'Vui lòng chọn kho bán hàng.',
        ];
    }

    public function managedStorages(User $user): Collection
    {
        if (! in_array((int) $user->role_id, self::MANAGER_ROLE_IDS, true)) {
            return collect();
        }

        return $this->managedStorageQuery($user)
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    private function managedStorageQuery(User $user): Builder
    {
        $ownerIds = collect([(int) $user->id])
            ->merge(
                User::query()
                    ->where('manager_id', $user->id)
                    ->where('role_id', self::BRANCH_ROLE_ID)
                    ->pluck('id')
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return Storage::query()->whereIn('user_id', $ownerIds);
    }

    private function storeSelection(User $user, int $storageId): void
    {
        Session::put($this->sessionKey($user), $storageId);
    }

    private function forgetSelection(User $user): void
    {
        Session::forget($this->sessionKey($user));
    }

    private function sessionKey(User $user): string
    {
        return self::SESSION_KEY_PREFIX.$user->id;
    }
}
