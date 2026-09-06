<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SendMailInfo;
use App\Models\Storage;
use App\Models\Roles;
use App\Models\User;
use App\Services\EmployeeDeletionService;
use App\Services\SaleStorageResolver;
use App\Support\BranchContext;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class EmployeeController extends Controller
{

    public function __construct(
        private SaleStorageResolver $saleStorageResolver,
        private BranchContext $branchContext,
        private EmployeeDeletionService $employeeDeletionService,
    )
    {
    }

    public function index(Request $request)
    {
       
        $title = "Nhân viên bán hàng";
        $mode = 'employees';
        if ($request->ajax()) {
            $searchText = trim((string) $request->query('s'));

            $employees = $this->employeeQuery()
                ->when($searchText !== '', function (Builder $query) use ($searchText) {
                    $query->where(function (Builder $query) use ($searchText) {
                        $query->where('name', 'like', "%{$searchText}%")
                            ->orWhere('email', 'like', "%{$searchText}%")
                            ->orWhere('phone', 'like', "%{$searchText}%");

                        if (ctype_digit($searchText)) {
                            $query->orWhere('id', (int) $searchText);
                        }
                    });
                })
                ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [Auth::id()])
                ->latest()
                ->paginate(10)
                ->appends($request->query());

            $adminWorkplaceLabel = $this->adminWorkplaceLabel(Auth::user());
            $html = view('admin.employee.table', compact('employees', 'mode', 'adminWorkplaceLabel'))->render();

            return response()->json(['html' => $html]);
        }

        return view('admin.employee.index', compact('title'));
    }

    public function create()
    {
        $title = "Thêm nhân viên";
        $api = '/admin/employees';
        $user = null;
        $storages = $this->storageOptions();
        $requiresStorage = true;
        $accountType = 'employee';
        $adminWorkplaceLabel = null;

        return view('admin.employee.form', compact('title', 'api', 'user', 'storages', 'requiresStorage', 'accountType', 'adminWorkplaceLabel'));
    }

    public function store(Request $request)
    {
        $credentials = $this->validateRequest($request);
        $plainPassword = $credentials['password'];

        try {
            $user = DB::transaction(function () use ($credentials, $request) {
                if ($avatar = $this->storeAvatar($request)) {
                    $credentials['img_url'] = $avatar;
                }

                $credentials['role_id'] = Roles::staffId();
                $credentials['manager_id'] = Auth::id();
                $credentials['password'] = Hash::make($credentials['password']);
                $actor = $this->authorizedManager();
                $storageId = $credentials['storage_id'] ?? null;
                $storage = $storageId === null
                    ? null
                    : $this->branchContext
                        ->scope(Storage::query(), $actor)
                        ->findOrFail($storageId);
                $credentials['branch_id'] = $actor->isAdministrator()
                    ? $storage?->branch_id
                    : $this->branchContext->branchId($actor);

                return User::create($credentials);
            });
        } catch (Throwable $e) {
            Log::error('Failed to create employee account.', [
                'manager_id' => Auth::id(),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return errorResponse('Có lỗi xảy ra, vui lòng thử lại sau!');
        }

        $this->sendAccountInfoEmail($user, $plainPassword);

        return successResponse(
            message: 'Tạo tài khoản nhân viên thành công.',
            data: ['redirect' => '/admin/employees'],
            code: Response::HTTP_CREATED,
            isToastr: true
        );
    }

    private function sendAccountInfoEmail(User $user, string $plainPassword): void
    {
        try {
            Mail::to($user->email)->send(new SendMailInfo($user, $plainPassword));
        } catch (Throwable $e) {
            Log::error('Failed to send employee account email.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    private function storageOptions()
    {
        $user = $this->authorizedManager();

        return $this->branchContext
            ->scope(Storage::query(), $user)
            ->whereNotNull('branch_id')
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);
    }

    public function edit(string $id)
    {
        $user = $this->employeeQuery()->findOrFail($id);
        $isAdmin = $this->isAdminAccount($user);
        $title = $isAdmin ? "Sửa tài khoản Admin - $user->name" : "Sửa tài khoản nhân viên - $user->name";
        $api = "/admin/employees/$user->id";
        $storages = $isAdmin ? collect() : $this->storageOptions();
        $requiresStorage = ! $isAdmin;
        $accountType = $isAdmin ? 'administrator' : 'employee';
        $adminWorkplaceLabel = $isAdmin ? $this->adminWorkplaceLabel($user) : null;

        return view('admin.employee.form', compact('title', 'api', 'user', 'storages', 'requiresStorage', 'accountType', 'adminWorkplaceLabel'));
    }

    public function update(Request $request, $id)
    {
        if (! $user = $this->employeeQuery()->find($id)) {
            return errorResponse(message: 'Tài khoản không tồn tại', code: Response::HTTP_NOT_FOUND);
        }

        if ($this->isAdminAccount($user) && (int) $user->id !== (int) Auth::id()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $accountType = $this->isAdminAccount($user) ? 'administrator' : 'employee';
        $credentials = $this->validateRequest($request, $id, $accountType);

        return transaction(function () use ($credentials, $request, $user, $accountType) {

            if (empty($credentials['password'])) {
                unset($credentials['password']);
            } else {
                $credentials['password'] = Hash::make($credentials['password']);
            }

            if ($avatar = $this->storeAvatar($request)) {
                $credentials['img_url'] = $avatar;
            }

            if ($accountType === 'employee') {
                $actor = $this->authorizedManager();
                $storage = $this->branchContext
                    ->scope(Storage::query(), $actor)
                    ->findOrFail($credentials['storage_id']);
                $credentials['branch_id'] = $actor->isAdministrator()
                    ? $storage->branch_id
                    : $this->branchContext->branchId($actor);
            }

            $user->update($credentials);

            if ($accountType === 'administrator') {
                Auth::setUser($user->fresh('userInfo'));
            }

            return successResponse(
                message: $accountType === 'administrator'
                    ? 'Cập nhật tài khoản Admin thành công.'
                    : 'Cập nhật tài khoản nhân viên thành công.',
                data: ['redirect' => '/admin/employees'],
                code: Response::HTTP_OK,
                isToastr: true
            );
        });
    }

    public function destroy(User $employee)
    {
        try {
            $this->employeeDeletionService->delete($this->authorizedManager(), $employee);

            return response()->json([
                'message' => 'Xóa nhân viên thành công.',
            ], Response::HTTP_OK);
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_CONFLICT);
        } catch (ValidationException | HttpExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Không thể xóa nhân viên.', [
                'employee_id' => $employee->getKey(),
                'actor_id' => Auth::id(),
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Không thể xóa nhân viên. Vui lòng kiểm tra dữ liệu liên quan.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function validateRequest($request, $id = null, string $accountType = 'employee')
    {
        $rules = [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'phone'      => ['required', 'string', 'max:15', Rule::unique('users', 'phone')->ignore($id)],
            'img_url'    => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'password'   => [$id ? 'nullable' : 'required', 'string', 'min:6'],
            'role_id'    => ['prohibited'],
            'manager_id' => ['prohibited'],
            'branch_id'  => ['prohibited'],
        ];

        if ($accountType === 'administrator') {
            $rules['address'] = ['prohibited'];
            $rules['storage_id'] = ['prohibited'];
            $rules['status'] = ['prohibited'];
        } else {
            $rules['address'] = ['nullable', 'string', 'max:255'];
            $rules['storage_id'] = [
                $id ? 'required' : 'nullable',
                'integer',
                Rule::exists('storages', 'id')->where(
                    fn ($query) => $query->whereIn('id', $this->storageOptions()->modelKeys())
                ),
            ];
            $rules['status'] = ['required', 'in:active,inactive,locked'];
        }

        return $this->validate($request, $rules, __('request.messages'), [
            'name'       => 'Tên tài khoản',
            'email'      => 'Email',
            'phone'      => 'Số điện thoại',
            'password'   => 'Mật khẩu',
            'address'    => 'Địa chỉ',
            'storage_id' => 'Kho hàng',
            'status'     => 'Trạng thái',
            'img_url'    => 'Ảnh đại diện',
            'role_id'    => 'Vai trò',
            'manager_id' => 'Người quản lý',
        ]);
    }

    private function employeeQuery(): Builder
    {
        $user = $this->authorizedManager();
        $staff = User::query()->whereIn('role_id', Roles::staffIds());

        if (! $user->isAdministrator()) {
            $this->branchContext->scope($staff, $user);

            return $staff->with('storage');
        }

        return User::query()
            ->with('storage')
            ->where(function (Builder $query) use ($staff) {
                $query->whereKey(Auth::id())
                    ->orWhereIn('id', $staff->select('id'));
            });
    }

    private function authorizedManager(): User
    {
        $user = Auth::user();

        abort_unless($user && ($user->isAdministrator() || $user->isAdminStore()), Response::HTTP_FORBIDDEN);

        return $user;
    }

    private function isAdminAccount(User $user): bool
    {
        return $user->isAdministrator();
    }

    private function adminWorkplaceLabel(?User $user): string
    {
        $defaultStorageName = trim((string) config('pos.default_storage_name', 'Kho A'));
        $defaultStorageName = $defaultStorageName !== '' ? $defaultStorageName : 'Kho A';

        if ($user) {
            $context = $this->saleStorageResolver->saleStorageContext($user);
            $selectedStorage = $context['selectedStorage'] ?? null;

            if ($selectedStorage instanceof Storage) {
                return "Toàn hệ thống · Kho bán mặc định: {$selectedStorage->name}";
            }
        }

        return "Toàn hệ thống · Kho bán mặc định: {$defaultStorageName}";
    }

    private function storeAvatar(Request $request): ?string
    {
        if (! $request->hasFile('img_url')) {
            return null;
        }

        return uploadImages('img_url', 'avatar');
    }
}
