<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Categories;
use App\Models\Client;
use App\Models\Company;
use App\Models\Product;
use App\Models\Storage;
use App\Models\User;
use App\Services\ClientService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class BulkController extends Controller
{
    private const ADMIN_ROLE_ID = 1;
    private const BRANCH_ROLE_ID = 2;
    private const EMPLOYEE_ROLE_ID = 3;

    private const DELETE_MODELS = [
        'Brand' => Brand::class,
        'Categories' => Categories::class,
        'Client' => Client::class,
        'Company' => Company::class,
        'Product' => Product::class,
        'Storage' => Storage::class,
        'User' => User::class,
    ];

    private const STATUS_MODELS = [
        'Brand' => Brand::class,
        'Categories' => Categories::class,
        'Company' => Company::class,
        'Product' => Product::class,
        'Storage' => Storage::class,
    ];

    public function __construct(private ClientService $clientService)
    {
    }

    public function bulk(string $type, Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'model' => 'required|string',
        ], __('request.messages'));

        $ids = array_values(array_unique($request->input('ids', [])));
        $model = $request->input('model');
        $modelClass = $this->modelClassForAction($type, $model);

        if (! $modelClass) {
            return errorResponse('Hành động không hợp lệ hoặc model không được phép thao tác!', 400);
        }

        if (empty($ids)) {
            return errorResponse('Vui lòng chọn ít nhất 1 bản ghi!', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($type === 'delete' && $modelClass === User::class) {
            return $this->deactivateUsers($ids);
        }

        if ($type === 'delete' && $modelClass === Client::class) {
            try {
                $this->clientService->deleteClients($ids);

                return response()->json([
                    'message' => 'Ngừng hoạt động khách hàng thành công!',
                ]);
            } catch (DomainException $e) {
                return errorResponse($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        if ($type === 'delete' && $modelClass === Product::class) {
            $products = Product::query()
                ->where('user_id', Auth::id())
                ->whereIn('id', $ids);

            if ((clone $products)->count() !== count($ids)) {
                abort(Response::HTTP_NOT_FOUND);
            }

            if ((clone $products)->whereHas('imeis')->exists()) {
                return errorResponse(
                    'Không thể xóa sản phẩm vì sản phẩm đang có dữ liệu IMEI.',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }

        return transaction(function () use ($modelClass, $ids, $type) {
            switch ($type) {
                case 'delete':
                    $modelClass::whereIn('id', $ids)->delete();

                    return response()->json(['message' => 'Xóa thành công!']);

                case 'status':
                    $modelClass::whereIn('id', $ids)
                        ->update(['status' => DB::raw('NOT status')]);

                    return successResponse('Cập nhật trạng thái thành công!');

                default:
                    return errorResponse('Hành động không hợp lệ!', 400);
            }
        });
    }

    private function modelClassForAction(string $type, ?string $model): ?string
    {
        return match ($type) {
            'delete' => self::DELETE_MODELS[$model] ?? null,
            'status' => self::STATUS_MODELS[$model] ?? null,
            default => null,
        };
    }

    private function deactivateUsers(array $ids)
    {
        $ids = array_map('intval', $ids);
        $authUser = Auth::user();
        $authId = (int) Auth::id();

        if (! $authUser || (int) $authUser->role_id !== self::ADMIN_ROLE_ID) {
            return errorResponse(
                'Không có quyền ngừng hoạt động tài khoản nhân viên.',
                Response::HTTP_FORBIDDEN
            );
        }

        if (in_array($authId, $ids, true)) {
            return errorResponse(
                'Không thể ngừng hoạt động chính tài khoản đang đăng nhập.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $users = User::query()
            ->whereIn('id', $ids)
            ->get(['id', 'role_id']);

        if ($users->count() !== count($ids)) {
            return errorResponse('Tài khoản không tồn tại.', Response::HTTP_NOT_FOUND);
        }

        if ($users->contains(fn (User $user) => (int) $user->role_id === self::ADMIN_ROLE_ID)) {
            return errorResponse(
                'Không thể ngừng hoạt động tài khoản Admin.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($users->contains(fn (User $user) => (int) $user->role_id !== self::EMPLOYEE_ROLE_ID)) {
            return errorResponse(
                'Chỉ được ngừng hoạt động tài khoản nhân viên.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $managedIds = $this->managedEmployeeQuery()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($managedIds) !== count($ids)) {
            return errorResponse(
                'Không có quyền thao tác với một hoặc nhiều tài khoản đã chọn.',
                Response::HTTP_FORBIDDEN
            );
        }

        User::query()
            ->whereIn('id', $ids)
            ->update(['status' => 'inactive']);

        return response()->json(['message' => 'Ngừng hoạt động nhân viên thành công!']);
    }

    private function managedEmployeeQuery(): Builder
    {
        $managedUserIds = $this->managedUserIds();
        $managedStorageIds = $this->managedStorageIds();

        return User::query()
            ->where('role_id', self::EMPLOYEE_ROLE_ID)
            ->where(function (Builder $query) use ($managedUserIds, $managedStorageIds) {
                if (empty($managedUserIds) && empty($managedStorageIds)) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                if (!empty($managedUserIds)) {
                    $query->whereIn('manager_id', $managedUserIds);
                }

                if (!empty($managedStorageIds)) {
                    $query->orWhereIn('storage_id', $managedStorageIds);
                }
            });
    }

    private function managedUserIds(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        $branchIds = User::query()
            ->where('manager_id', $user->id)
            ->where('role_id', self::BRANCH_ROLE_ID)
            ->pluck('id');

        return collect([(int) $user->id])
            ->merge($branchIds)
            ->unique()
            ->values()
            ->all();
    }

    private function managedStorageIds(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        $storageIds = Storage::query()
            ->whereIn('user_id', $this->managedUserIds())
            ->pluck('id');

        return collect([$user->storage_id])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->merge($storageIds)
            ->unique()
            ->values()
            ->all();
    }
}
