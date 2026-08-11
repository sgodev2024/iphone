<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SendMailInfo;
use App\Models\Storage;
use App\Models\User;
use App\Services\SaleStorageResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EmployeeController extends Controller
{
    private const ADMIN_ROLE_ID = 1;
    private const BRANCH_ROLE_ID = 2;
    private const EMPLOYEE_ROLE_ID = 3;

    public function __construct(private SaleStorageResolver $saleStorageResolver)
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

                $credentials['role_id'] = self::EMPLOYEE_ROLE_ID;
                $credentials['manager_id'] = Auth::id();
                $credentials['password'] = Hash::make($credentials['password']);
                $credentials['branch_id'] = Auth::user()->branch_id;

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
        return Storage::query()
            ->whereIn('id', $this->managedStorageIds())
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function edit(string $id)
    {
        $user = $this->employeeQuery()->findOrFail($id);
        $isAdmin = $this->isAdminAccount($user);
        $title = $isAdmin ? "Sửa tài khoản Admin - $user->name" : "Sửa tài khoản nhân viên - $user->name";
        $api = "/admin/employees/$user->id";
        $storages = $isAdmin ? collect() : $this->storageOptions();
        $requiresStorage = ! $isAdmin;
        $accountType = $isAdmin ? 'admin' : 'employee';
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

        $accountType = $this->isAdminAccount($user) ? 'admin' : 'employee';
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

            $user->update($credentials);

            if ($accountType === 'admin') {
                Auth::setUser($user->fresh('userInfo'));
            }

            return successResponse(
                message: $accountType === 'admin'
                    ? 'Cập nhật tài khoản Admin thành công.'
                    : 'Cập nhật tài khoản nhân viên thành công.',
                data: ['redirect' => '/admin/employees'],
                code: Response::HTTP_OK,
                isToastr: true
            );
        });
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
        ];

        if ($accountType === 'admin') {
            $rules['address'] = ['prohibited'];
            $rules['storage_id'] = ['prohibited'];
            $rules['status'] = ['prohibited'];
        } else {
            $rules['address'] = ['nullable', 'string', 'max:255'];
            $rules['storage_id'] = [
                'required',
                'integer',
                Rule::exists('storages', 'id')->where(fn ($query) => $query->whereIn('id', $this->managedStorageIds())),
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
        $managedUserIds = $this->managedUserIds();
        $managedStorageIds = $this->managedStorageIds();

        return User::query()
            ->with('storage')
            ->where(function (Builder $query) use ($managedUserIds, $managedStorageIds) {
                $query->where(function (Builder $query) {
                    $query->whereKey(Auth::id())
                        ->where('role_id', self::ADMIN_ROLE_ID);
                })->orWhere(function (Builder $query) use ($managedUserIds, $managedStorageIds) {
                    $query->where('role_id', self::EMPLOYEE_ROLE_ID)
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
                });
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

    private function isAdminAccount(User $user): bool
    {
        return (int) $user->role_id === self::ADMIN_ROLE_ID;
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
