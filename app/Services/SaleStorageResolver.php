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

    private const SESSION_KEY_PREFIX = 'sale.storage_id.';

    private const UNASSIGNED_STAFF_STORAGE_MESSAGE = 'Nhân viên chưa được gán kho bán hàng. Vui lòng liên hệ Admin Store.';

    private const NO_ADMIN_STORE_STORAGE_MESSAGE = 'Chi nhánh chưa có kho bán hàng hoạt động.';

    private const NO_MANAGED_STORAGE_MESSAGE = 'Chưa có kho bán hàng hoạt động. Vui lòng tạo hoặc phân quyền kho.';

    private const SELECT_STORAGE_MESSAGE = 'Vui lòng chọn kho bán hàng.';

    public function resolveSaleStorageId(User $user, mixed $requestedStorageId = null): int
    {
        if ($user->isStaff()) {
            if (! $user->storage_id
                || ! Storage::query()->visibleTo($user)->whereKey($user->storage_id)->exists()
            ) {
                throw ValidationException::withMessages([
                    'storage_id' => self::UNASSIGNED_STAFF_STORAGE_MESSAGE,
                ]);
            }

            if ($requestedStorageId !== null && $requestedStorageId !== '') {
                $storageId = filter_var($requestedStorageId, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);

                if ($storageId === false || (int) $storageId !== (int) $user->storage_id) {
                    throw ValidationException::withMessages([
                        'storage_id' => 'Nhân viên không được bán hàng từ kho khác kho được phân công.',
                    ]);
                }
            }

            return (int) $user->storage_id;
        }

        $storages = $this->managedStorages($user);

        if ($storages->isEmpty()) {
            $this->forgetSelection($user);

            throw ValidationException::withMessages([
                'storage_id' => $this->noStorageMessage($user),
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

        $storage = $this->resolveManagedStorage($user, $storages);

        if (! $storage) {
            throw ValidationException::withMessages([
                'storage_id' => self::SELECT_STORAGE_MESSAGE,
            ]);
        }

        $this->storeSelection($user, (int) $storage->id);

        return (int) $storage->id;
    }

    public function selectSaleStorageId(User $user, mixed $requestedStorageId): int
    {
        if ($user->isStaff()) {
            throw ValidationException::withMessages([
                'storage_id' => 'Nhân viên không được tự thay đổi kho bán hàng.',
            ]);
        }

        return $this->resolveSaleStorageId($user, $requestedStorageId);
    }

    public function saleStorageContext(User $user): array
    {
        if ($user->isStaff()) {
            $storage = $user->storage_id
                ? Storage::query()->visibleTo($user)->find($user->storage_id)
                : null;

            return [
                'storages' => $storage ? collect([$storage]) : collect(),
                'selectedStorage' => $storage,
                'canSelectStorage' => false,
                'message' => $storage ? null : self::UNASSIGNED_STAFF_STORAGE_MESSAGE,
            ];
        }

        $storages = $this->managedStorages($user);

        if ($storages->isEmpty()) {
            $this->forgetSelection($user);

            return [
                'storages' => $storages,
                'selectedStorage' => null,
                'canSelectStorage' => false,
                'message' => $this->noStorageMessage($user),
            ];
        }

        $selectedStorage = $this->resolveManagedStorage($user, $storages);

        if (! $selectedStorage) {
            return [
                'storages' => $storages,
                'selectedStorage' => null,
                'canSelectStorage' => $storages->count() > 1,
                'message' => self::SELECT_STORAGE_MESSAGE,
            ];
        }

        $this->storeSelection($user, (int) $selectedStorage->id);

        return [
            'storages' => $storages,
            'selectedStorage' => $selectedStorage,
            'canSelectStorage' => $storages->count() > 1,
            'message' => null,
        ];
    }

    public function managedStorages(User $user): Collection
    {
        if (! $user->isAdministrator() && ! $user->isAdminStore()) {
            return collect();
        }

        return $this->managedStorageQuery($user)
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->filter(fn (Storage $storage) => $this->storageIsActive($storage))
            ->values();
    }

    private function managedStorageQuery(User $user): Builder
    {
        return Storage::query()->visibleTo($user);
    }

    private function resolveManagedStorage(User $user, Collection $storages): ?Storage
    {
        if ($storages->count() === 1) {
            return $storages->first();
        }

        $sessionStorage = $this->resolveSessionStorage($user, $storages);

        if ($sessionStorage) {
            return $sessionStorage;
        }

        return $this->resolveDefaultManagedStorage($storages);
    }

    private function resolveSessionStorage(User $user, Collection $storages): ?Storage
    {
        $selectedStorageId = Session::get($this->sessionKey($user));

        if ($selectedStorageId === null || $selectedStorageId === '') {
            return null;
        }

        $storageId = filter_var($selectedStorageId, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($storageId === false) {
            $this->forgetSelection($user);

            return null;
        }

        $storage = $storages->firstWhere('id', (int) $storageId);

        if (! $storage) {
            $this->forgetSelection($user);
        }

        return $storage;
    }

    private function resolveDefaultManagedStorage(Collection $storages): ?Storage
    {
        $configuredStorageId = $this->configuredDefaultStorageId();

        if ($configuredStorageId !== null) {
            $storage = $storages->firstWhere('id', $configuredStorageId);

            if ($storage) {
                return $storage;
            }
        }

        $defaultStorageName = trim((string) config('pos.default_storage_name', 'Kho A'));
        $defaultStorageName = $defaultStorageName === '' ? 'Kho A' : $defaultStorageName;

        $matches = $storages
            ->filter(fn (Storage $storage) => trim((string) $storage->name) === $defaultStorageName)
            ->values();

        return $matches->count() === 1 ? $matches->first() : null;
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

        return $storageId === false ? null : (int) $storageId;
    }

    private function noStorageMessage(User $user): string
    {
        return $user->isAdminStore()
            ? self::NO_ADMIN_STORE_STORAGE_MESSAGE
            : self::NO_MANAGED_STORAGE_MESSAGE;
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
