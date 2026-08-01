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

        $storage = $this->resolveDefaultManagedStorage($storages);
        $this->storeSelection($user, (int) $storage->id);

        return (int) $storage->id;
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

        try {
            $selectedStorage = $this->resolveDefaultManagedStorage($storages);
            $this->storeSelection($user, (int) $selectedStorage->id);
        } catch (ValidationException $exception) {
            $this->forgetSelection($user);

            return [
                'storages' => $storages,
                'selectedStorage' => null,
                'canSelectStorage' => false,
                'message' => collect($exception->errors())->flatten()->first()
                    ?: 'Chưa cấu hình kho bán hàng mặc định.',
            ];
        }

        return [
            'storages' => $storages,
            'selectedStorage' => $selectedStorage,
            'canSelectStorage' => false,
            'message' => null,
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

    private function resolveDefaultManagedStorage(Collection $storages): Storage
    {
        $configuredStorageId = $this->configuredDefaultStorageId();

        if ($configuredStorageId !== null) {
            $storage = Storage::query()->find($configuredStorageId);

            if (! $storage) {
                throw ValidationException::withMessages([
                    'storage_id' => 'Kho bán hàng mặc định không tồn tại.',
                ]);
            }

            if (! $storages->contains('id', $configuredStorageId)) {
                throw ValidationException::withMessages([
                    'storage_id' => 'Kho bán hàng mặc định không thuộc quyền quản lý của tài khoản.',
                ]);
            }

            if (! $this->storageIsActive($storage)) {
                throw ValidationException::withMessages([
                    'storage_id' => 'Kho bán hàng mặc định không hoạt động.',
                ]);
            }

            return $storages->firstWhere('id', $configuredStorageId) ?: $storage;
        }

        $defaultStorageName = trim((string) config('pos.default_storage_name', 'Kho A'));
        $defaultStorageName = $defaultStorageName === '' ? 'Kho A' : $defaultStorageName;

        $matches = $storages
            ->filter(fn (Storage $storage) => trim((string) $storage->name) === $defaultStorageName)
            ->values();

        if ($matches->isEmpty()) {
            throw ValidationException::withMessages([
                'storage_id' => 'Chưa cấu hình kho bán hàng mặc định.',
            ]);
        }

        if ($matches->count() > 1) {
            throw ValidationException::withMessages([
                'storage_id' => "Có nhiều kho tên {$defaultStorageName} trong phạm vi quản lý. Vui lòng cấu hình POS_DEFAULT_STORAGE_ID.",
            ]);
        }

        $storage = $matches->first();

        if (! $this->storageIsActive($storage)) {
            throw ValidationException::withMessages([
                'storage_id' => 'Kho bán hàng mặc định không hoạt động.',
            ]);
        }

        return $storage;
    }

    private function configuredDefaultStorageId(): ?int
    {
        $configuredStorageId = config('pos.default_storage_id');

        if ($configuredStorageId === null || $configuredStorageId === '') {
            return null;
        }

        $storageId = filter_var($configuredStorageId, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($storageId === false) {
            throw ValidationException::withMessages([
                'storage_id' => 'POS_DEFAULT_STORAGE_ID không hợp lệ.',
            ]);
        }

        return (int) $storageId;
    }

    private function storageIsActive(Storage $storage): bool
    {
        $attributes = $storage->getAttributes();

        if (array_key_exists('is_active', $attributes)) {
            return in_array($attributes['is_active'], [true, 1, '1'], true);
        }

        if (array_key_exists('status', $attributes)) {
            return in_array($attributes['status'], [true, 1, '1', 'active', 'published'], true);
        }

        return true;
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
