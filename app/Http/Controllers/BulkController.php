<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Categories;
use App\Models\Client;
use App\Models\Company;
use App\Models\Product;
use App\Models\Roles;
use App\Models\Storage;
use App\Models\User;
use App\Services\ClientService;
use App\Support\BranchContext;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class BulkController extends Controller
{
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

    public function __construct(
        private ClientService $clientService,
        private BranchContext $branchContext,
    ) {}

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

        if (in_array($modelClass, [Client::class, Company::class], true)) {
            $allowed = $this->branchContext
                ->scope($modelClass::query(), Auth::user())
                ->whereIn('id', $ids)
                ->count();

            if ($allowed !== count($ids)) {
                abort(Response::HTTP_NOT_FOUND);
            }
        }

        if ($modelClass === Storage::class) {
            $allowed = Storage::query()
                ->visibleTo(Auth::user())
                ->whereIn('id', $ids)
                ->count();

            if ($allowed !== count($ids)) {
                abort(Response::HTTP_NOT_FOUND);
            }
        }

        if (in_array($modelClass, [Product::class, Categories::class], true)) {
            $permission = $modelClass === Product::class
                ? ($type === 'delete' ? 'product.delete' : 'product.update')
                : ($type === 'delete' ? 'category.delete' : 'category.update');
            abort_unless(Auth::user()?->hasPermission($permission), Response::HTTP_FORBIDDEN);

            $allowedQuery = $modelClass::query();
            $this->branchContext->scope($allowedQuery, Auth::user(), $modelClass === Product::class
                ? 'products.branch_id'
                : 'categories.branch_id');

            if ($allowedQuery->whereIn('id', $ids)->count() !== count($ids)) {
                abort(Response::HTTP_NOT_FOUND);
            }
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
            $user = Auth::user();
            abort_unless(
                $user?->hasPermission('product.delete'),
                Response::HTTP_FORBIDDEN
            );

            return DB::transaction(function () use ($ids, $user) {
                $products = Product::query()->whereIn('id', $ids);

                $this->branchContext->scope($products, $user, 'products.branch_id');
                if (! Schema::hasColumn('products', 'branch_id') && ! $user->isAdministrator()) {
                    $products->where('user_id', $user->id);
                }

                $productIds = (clone $products)
                    ->lockForUpdate()
                    ->pluck('id');

                if ($productIds->count() !== count($ids)) {
                    abort(Response::HTTP_NOT_FOUND);
                }

                if ($message = $this->productDeleteBlockMessage($products)) {
                    return errorResponse(
                        $message,
                        Response::HTTP_UNPROCESSABLE_ENTITY
                    );
                }

                $products->delete();

                return response()->json(['message' => 'Xóa thành công!']);
            }, 3);
        }

        return transaction(function () use ($modelClass, $ids, $type) {
            $query = $modelClass::query()->whereIn('id', $ids);
            if (in_array($modelClass, [Product::class, Categories::class], true)) {
                $this->branchContext->scope(
                    $query,
                    Auth::user(),
                    $modelClass === Product::class ? 'products.branch_id' : 'categories.branch_id'
                );
            }

            switch ($type) {
                case 'delete':
                    $query->delete();

                    return response()->json(['message' => 'Xóa thành công!']);

                case 'status':
                    $query->update(['status' => DB::raw('NOT status')]);

                    return successResponse('Cập nhật trạng thái thành công!');

                default:
                    return errorResponse('Hành động không hợp lệ!', 400);
            }
        });
    }

    private function productDeleteBlockMessage(Builder $products): ?string
    {
        if ((clone $products)
            ->whereHas('imeis', fn (Builder $query) => $query->withTrashed())
            ->exists()
        ) {
            return 'Không thể xóa sản phẩm vì sản phẩm đang có dữ liệu IMEI.';
        }

        if ((clone $products)->whereHas('orderDetails')->exists()) {
            return 'Không thể xóa sản phẩm vì sản phẩm đã phát sinh lịch sử bán hàng.';
        }

        if ((clone $products)->whereHas('importDetails')->exists()) {
            return 'Không thể xóa sản phẩm vì sản phẩm đã phát sinh lịch sử nhập hàng.';
        }

        if ((clone $products)->whereHas('productStorages')->exists()) {
            return 'Không thể xóa sản phẩm vì sản phẩm đang có dữ liệu tồn kho.';
        }

        $productIds = (clone $products)->pluck('id');
        $references = [
            [
                ['order_return_details'],
                'Không thể xóa sản phẩm vì sản phẩm đã phát sinh lịch sử bán hàng.',
            ],
            [
                ['import'],
                'Không thể xóa sản phẩm vì sản phẩm đã phát sinh lịch sử nhập hàng.',
            ],
            [
                ['check_detail', 'warehouse'],
                'Không thể xóa sản phẩm vì sản phẩm đã phát sinh dữ liệu kiểm kho.',
            ],
            [
                ['company_product'],
                'Không thể xóa sản phẩm vì sản phẩm đang có liên kết nhà cung cấp.',
            ],
            [
                ['carts', 'cart_detail'],
                'Không thể xóa sản phẩm vì sản phẩm đang được sử dụng trong giỏ hàng.',
            ],
        ];

        foreach ($references as [$tables, $message]) {
            foreach ($tables as $table) {
                if (Schema::hasTable($table)
                    && DB::table($table)->whereIn('product_id', $productIds)->exists()
                ) {
                    return $message;
                }
            }
        }

        return null;
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

        if (! $authUser || (! $authUser->isAdministrator() && ! $authUser->isAdminStore())) {
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

        return DB::transaction(function () use ($ids) {
            $managedIds = $this->managedEmployeeQuery()
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (count($managedIds) !== count($ids)) {
                return errorResponse(
                    'Không có quyền thao tác với một hoặc nhiều tài khoản đã chọn.',
                    Response::HTTP_FORBIDDEN
                );
            }

            $activeAdministratorIds = User::query()
                ->where('role_id', Roles::ADMINISTRATOR_ID)
                ->where('status', 'active')
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $deactivatesActiveAdministrator = array_intersect($ids, $activeAdministratorIds) !== [];
            $remainingActiveAdministrators = array_diff($activeAdministratorIds, $ids);

            if ($deactivatesActiveAdministrator && $remainingActiveAdministrators === []) {
                return errorResponse(
                    'Không thể ngừng hoạt động Administrator cuối cùng của hệ thống.',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            User::query()
                ->whereIn('id', $ids)
                ->update(['status' => 'inactive']);

            return response()->json(['message' => 'Ngừng hoạt động tài khoản thành công!']);
        }, 3);
    }

    private function managedEmployeeQuery(): Builder
    {
        $actor = Auth::user();

        if ($actor->isAdministrator()) {
            return User::query()->whereIn('role_id', [
                Roles::ADMINISTRATOR_ID,
                Roles::ADMIN_STORE_ID,
            ]);
        }

        return User::query()
            ->where('role_id', Roles::STAFF_ID)
            ->where('branch_id', $this->branchContext->branchId($actor));
    }
}
