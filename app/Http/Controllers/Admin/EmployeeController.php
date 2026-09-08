<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SendMailInfo;
use App\Models\Roles;
use App\Models\Storage;
use App\Models\User;
use App\Services\EmployeeDeletionService;
use App\Support\BranchContext;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class EmployeeController extends Controller
{
    private const STATUS_TRANSITIONS = [
        'active' => ['inactive'],
        'inactive' => ['active'],
        'locked' => ['active'],
    ];

    public function __construct(
        private BranchContext $branchContext,
        private EmployeeDeletionService $employeeDeletionService,
    ) {}

    public function index(Request $request)
    {
        $actor = $this->authorizedManager();

        // Fail closed before rendering even the non-AJAX shell page.
        if ($actor->isAdminStore()) {
            $this->branchContext->branchId($actor);
        }

        $title = 'Quản lý nhân viên';
        $mode = 'employees';
        if ($request->ajax()) {
            $searchText = trim((string) $request->query('s'));

            $employees = $this->employeeQuery($actor)
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

            $html = view('admin.employee.table', compact('employees', 'mode', 'actor'))->render();

            return response()->json(['html' => $html]);
        }

        return view('admin.employee.index', compact('title'));
    }

    public function create()
    {
        $actor = $this->authorizedManager();
        $isAdministrator = $actor->isAdministrator();
        $title = $isAdministrator ? 'Thêm tài khoản' : 'Thêm nhân viên';
        $api = '/admin/employees';
        $user = null;
        $storages = $isAdministrator ? collect() : $this->storageOptions($actor);
        $requiresStorage = ! $isAdministrator;
        $accountType = $isAdministrator ? 'managed_admin' : 'staff';
        $canSelectRole = $isAdministrator;
        $roleOptions = $this->roleOptions();
        $roleLabel = null;
        $branchName = $isAdministrator ? null : $this->currentBranchLabel($actor);
        $adminWorkplaceLabel = null;

        return view('admin.employee.form', compact(
            'title',
            'api',
            'user',
            'storages',
            'requiresStorage',
            'accountType',
            'canSelectRole',
            'roleOptions',
            'roleLabel',
            'branchName',
            'adminWorkplaceLabel',
        ));
    }

    public function store(Request $request)
    {
        $actor = $this->authorizedManager();
        $credentials = $this->validateRequest($request, actor: $actor);
        $plainPassword = $credentials['password'];

        try {
            $user = DB::transaction(function () use ($credentials, $request, $actor) {
                if ($avatar = $this->storeAvatar($request)) {
                    $credentials['img_url'] = $avatar;
                }

                $credentials['password'] = Hash::make($credentials['password']);

                if ($actor->isAdministrator()) {
                    $credentials['role_id'] = (int) $credentials['role_id'];
                    $credentials['manager_id'] = null;
                    $credentials['branch_id'] = null;
                    $credentials['storage_id'] = null;
                } else {
                    $branchId = $this->branchContext->branchId($actor);
                    $storageId = $credentials['storage_id'] ?? null;
                    $storage = $storageId === null
                        ? null
                        : Storage::query()
                            ->where('branch_id', $branchId)
                            ->findOrFail($storageId);

                    $credentials['role_id'] = Roles::STAFF_ID;
                    $credentials['manager_id'] = $actor->id;
                    $credentials['branch_id'] = $branchId;
                    $credentials['storage_id'] = $storage?->id;
                }

                return User::create($credentials);
            });
        } catch (ValidationException $exception) {
            throw $exception;
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

    private function storageOptions(?User $actor = null)
    {
        $actor ??= $this->authorizedManager();
        abort_unless($actor->isAdminStore(), Response::HTTP_FORBIDDEN);
        $branchId = $this->branchContext->branchId($actor);

        return Storage::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);
    }

    public function edit(string $id)
    {
        $actor = $this->authorizedManager();
        $user = $this->employeeQuery($actor)->findOrFail($id);
        $isManagedAdmin = $this->isManagedAdminAccount($user);
        $title = $isManagedAdmin ? "Sửa tài khoản - $user->name" : "Sửa tài khoản nhân viên - $user->name";
        $api = "/admin/employees/$user->id";
        $storages = $isManagedAdmin ? collect() : $this->storageOptions($actor);
        $requiresStorage = ! $isManagedAdmin;
        $accountType = $isManagedAdmin ? 'managed_admin' : 'staff';
        $canSelectRole = false;
        $roleOptions = $this->roleOptions();
        $roleLabel = $this->roleLabel($user);
        $branchName = $isManagedAdmin ? null : $this->currentBranchLabel($actor);
        $adminWorkplaceLabel = $isManagedAdmin ? $this->adminWorkplaceLabel($user) : null;

        return view('admin.employee.form', compact(
            'title',
            'api',
            'user',
            'storages',
            'requiresStorage',
            'accountType',
            'canSelectRole',
            'roleOptions',
            'roleLabel',
            'branchName',
            'adminWorkplaceLabel',
        ));
    }

    public function update(Request $request, $id)
    {
        $actor = $this->authorizedManager();

        if (! $user = $this->employeeQuery($actor)->find($id)) {
            return errorResponse(message: 'Tài khoản không tồn tại', code: Response::HTTP_NOT_FOUND);
        }

        $isManagedAdmin = $this->isManagedAdminAccount($user);
        $credentials = $this->validateRequest($request, $id, $actor);

        try {
            return DB::transaction(function () use ($credentials, $request, $user, $actor, $isManagedAdmin) {
                if (empty($credentials['password'])) {
                    unset($credentials['password']);
                } else {
                    $credentials['password'] = Hash::make($credentials['password']);
                }

                if ($avatar = $this->storeAvatar($request)) {
                    $credentials['img_url'] = $avatar;
                }

                if (! $isManagedAdmin) {
                    $branchId = $this->branchContext->branchId($actor);
                    $storageId = $credentials['storage_id'] ?? null;
                    $storage = $storageId === null
                        ? null
                        : Storage::query()
                            ->where('branch_id', $branchId)
                            ->findOrFail($storageId);
                    $credentials['role_id'] = Roles::STAFF_ID;
                    $credentials['manager_id'] = $actor->id;
                    $credentials['branch_id'] = $branchId;
                    $credentials['storage_id'] = $storage?->id;
                } else {
                    $credentials['manager_id'] = null;
                    $credentials['storage_id'] = null;

                    if ((int) $user->role_id === Roles::ADMINISTRATOR_ID) {
                        $credentials['branch_id'] = null;
                    }
                }

                if ((string) $user->status !== (string) $credentials['status']) {
                    $this->ensureStatusChangeAllowed($actor, $user, $credentials['status']);
                }

                $user->update($credentials);

                if ((int) $user->id === (int) Auth::id()) {
                    Auth::setUser($user->fresh('userInfo'));
                }

                return successResponse(
                    message: $isManagedAdmin
                        ? 'Cập nhật tài khoản quản trị thành công.'
                        : 'Cập nhật tài khoản nhân viên thành công.',
                    data: ['redirect' => '/admin/employees'],
                    code: Response::HTTP_OK,
                    isToastr: true
                );
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Failed to update employee account.', [
                'employee_id' => $user->id,
                'manager_id' => Auth::id(),
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return errorResponse('Có lỗi xảy ra, vui lòng thử lại sau!');
        }
    }

    public function updateStatus(Request $request, string $id)
    {
        $actor = $this->authorizedManager();
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive', 'locked'])],
        ], __('request.messages'), [
            'status' => 'Trạng thái',
        ]);

        return DB::transaction(function () use ($actor, $id, $validated) {
            $employee = $this->employeeQuery($actor)
                ->lockForUpdate()
                ->find($id);

            if (! $employee) {
                return errorResponse(message: 'Tài khoản không tồn tại', code: Response::HTTP_NOT_FOUND);
            }

            $currentStatus = (string) $employee->status;
            $targetStatus = $validated['status'];

            $this->ensureStatusTransitionAllowed($actor, $employee, $targetStatus);
            $employee->update(['status' => $targetStatus]);

            $message = match ([$currentStatus, $targetStatus]) {
                ['active', 'inactive'] => 'Ngừng hoạt động tài khoản thành công.',
                ['inactive', 'active'] => 'Kích hoạt lại tài khoản thành công.',
                ['locked', 'active'] => 'Mở khóa tài khoản thành công.',
            };

            return response()->json(['message' => $message]);
        }, 3);
    }

    public function destroy(User $employee)
    {
        try {
            $this->employeeDeletionService->delete($this->authorizedManager(), $employee);

            return response()->json([
                'message' => 'Xóa tài khoản thành công.',
            ], Response::HTTP_OK);
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_CONFLICT);
        } catch (ValidationException|HttpExceptionInterface $exception) {
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

    private function validateRequest(Request $request, $id = null, ?User $actor = null): array
    {
        $actor ??= $this->authorizedManager();
        $avatarConfig = config('uploads.avatar');
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'phone' => ['required', 'string', 'max:15', Rule::unique('users', 'phone')->ignore($id)],
            'img_url' => [
                'nullable',
                'image',
                'mimes:'.implode(',', $avatarConfig['extensions']),
                'mimetypes:'.implode(',', $avatarConfig['mime_types']),
                'max:'.$avatarConfig['max_kilobytes'],
            ],
            'password' => [$id ? 'nullable' : 'required', 'string', 'min:6'],
            'manager_id' => ['prohibited'],
            'branch_id' => ['prohibited'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,locked'],
        ];

        if ($actor->isAdministrator()) {
            $rules['role_id'] = $id === null
                ? ['required', 'integer', Rule::in([Roles::ADMINISTRATOR_ID, Roles::ADMIN_STORE_ID])]
                : ['prohibited'];
            $rules['storage_id'] = ['prohibited'];
        } else {
            // Creation never trusts a submitted role: it is excluded and forced to Staff.
            $rules['role_id'] = $id === null ? ['exclude'] : ['prohibited'];
            $branchId = $this->branchContext->branchId($actor);
            $rules['storage_id'] = [
                'nullable',
                'integer',
                Rule::exists('storages', 'id')->where(
                    fn ($query) => $query->where('branch_id', $branchId)
                ),
            ];
        }

        $messages = array_merge(__('request.messages'), [
            'img_url.image' => $avatarConfig['format_message'],
            'img_url.mimes' => $avatarConfig['format_message'],
            'img_url.mimetypes' => $avatarConfig['format_message'],
            'img_url.max' => $avatarConfig['size_message'],
        ]);

        return $this->validate($request, $rules, $messages, [
            'name' => 'Tên tài khoản',
            'email' => 'Email',
            'phone' => 'Số điện thoại',
            'password' => 'Mật khẩu',
            'address' => 'Địa chỉ',
            'storage_id' => 'Kho hàng',
            'status' => 'Trạng thái',
            'img_url' => 'Ảnh đại diện',
            'role_id' => 'Vai trò',
            'manager_id' => 'Người quản lý',
        ]);
    }

    private function employeeQuery(?User $actor = null): Builder
    {
        $actor ??= $this->authorizedManager();
        $query = User::query()->with(['storage', 'branch', 'administeredBranch']);

        if ($actor->isAdministrator()) {
            return $query->whereIn('role_id', [
                Roles::ADMINISTRATOR_ID,
                Roles::ADMIN_STORE_ID,
            ]);
        }

        return $query
            ->where('role_id', Roles::STAFF_ID)
            ->where('branch_id', $this->branchContext->branchId($actor));
    }

    private function ensureStatusTransitionAllowed(User $actor, User $employee, string $targetStatus): void
    {
        $this->ensureStatusChangeAllowed($actor, $employee, $targetStatus);

        $allowedTargets = self::STATUS_TRANSITIONS[(string) $employee->status] ?? [];

        if (! in_array($targetStatus, $allowedTargets, true)) {
            throw ValidationException::withMessages([
                'status' => ['Chuyển trạng thái tài khoản không hợp lệ.'],
            ]);
        }
    }

    private function ensureStatusChangeAllowed(User $actor, User $employee, string $targetStatus): void
    {
        if ((int) $actor->getKey() === (int) $employee->getKey()) {
            throw ValidationException::withMessages([
                'status' => ['Không thể thay đổi trạng thái chính tài khoản đang đăng nhập.'],
            ]);
        }

        if ((int) $employee->role_id !== Roles::ADMINISTRATOR_ID || $targetStatus === 'active') {
            return;
        }

        $remainingActiveAdministrators = User::query()
            ->where('role_id', Roles::ADMINISTRATOR_ID)
            ->where('status', 'active')
            ->whereKeyNot($employee->getKey())
            ->lockForUpdate()
            ->get(['id'])
            ->count();

        if ($remainingActiveAdministrators === 0) {
            throw ValidationException::withMessages([
                'status' => ['Không thể ngừng hoạt động Administrator cuối cùng của hệ thống.'],
            ]);
        }
    }

    private function authorizedManager(): User
    {
        $user = Auth::user();

        abort_unless($user && ($user->isAdministrator() || $user->isAdminStore()), Response::HTTP_FORBIDDEN);

        return $user;
    }

    private function isManagedAdminAccount(User $user): bool
    {
        return in_array((int) $user->role_id, [
            Roles::ADMINISTRATOR_ID,
            Roles::ADMIN_STORE_ID,
        ], true);
    }

    private function adminWorkplaceLabel(?User $user): string
    {
        if (! $user || (int) $user->role_id === Roles::ADMINISTRATOR_ID) {
            return 'Toàn hệ thống';
        }

        $branch = $user->administeredBranch ?: $user->branch;

        return $branch?->name ?: 'Chưa được gán chi nhánh';
    }

    private function currentBranchLabel(User $actor): string
    {
        $branchId = $this->branchContext->branchId($actor);

        if (Schema::hasTable('branches') && Schema::hasColumn('branches', 'name')) {
            $name = DB::table('branches')->where('id', $branchId)->value('name');

            if (is_string($name) && trim($name) !== '') {
                return $name;
            }
        }

        return "Chi nhánh #{$branchId}";
    }

    private function roleOptions(): array
    {
        return [
            Roles::ADMINISTRATOR_ID => 'Administrator',
            Roles::ADMIN_STORE_ID => 'Admin Store',
        ];
    }

    private function roleLabel(User $user): string
    {
        return match ((int) $user->role_id) {
            Roles::ADMINISTRATOR_ID => 'Administrator',
            Roles::ADMIN_STORE_ID => 'Admin Store',
            Roles::STAFF_ID => 'Nhân viên',
            default => 'Không xác định',
        };
    }

    private function storeAvatar(Request $request): ?string
    {
        if (! $request->hasFile('img_url')) {
            return null;
        }

        try {
            return uploadImages('img_url', 'avatar');
        } catch (Throwable $exception) {
            Log::warning('Failed to process employee avatar.', [
                'employee_id' => $request->route('employee'),
                'manager_id' => Auth::id(),
                'mime_type' => $request->file('img_url')?->getMimeType(),
                'size' => $request->file('img_url')?->getSize(),
                'error' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'img_url' => config('uploads.avatar.processing_message'),
            ]);
        }
    }
}
