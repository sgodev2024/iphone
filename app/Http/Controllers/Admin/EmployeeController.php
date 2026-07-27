<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SendMailInfo;
use App\Models\Storage;
use App\Models\User;
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
    private const BRANCH_ROLE_ID = 2;
    private const EMPLOYEE_ROLE_ID = 3;

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
                ->latest()
                ->paginate(10)
                ->appends($request->query());

            $html = view('admin.employee.table', compact('employees', 'mode'))->render();

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

        return view('admin.employee.form', compact('title', 'api', 'user', 'storages', 'requiresStorage'));
    }

    public function store(Request $request)
    {
        $credentials = $this->validateRequest($request);
        $plainPassword = $credentials['password'];

        try {
            $user = DB::transaction(function () use ($credentials) {
                $credentials['role_id'] = self::EMPLOYEE_ROLE_ID;
                $credentials['manager_id'] = Auth::id();
                $credentials['password'] = Hash::make($credentials['password']);

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
        $title = "Sửa tài khoản nhân viên - $user->name";
        $api = "/admin/employees/$user->id";
        $storages = $this->storageOptions();
        $requiresStorage = true;

        return view('admin.employee.form', compact('title', 'api', 'user', 'storages', 'requiresStorage'));
    }

    public function update(Request $request, $id)
    {
        $credentials = $this->validateRequest($request, $id);

        return transaction(function () use ($credentials, $id) {

            if (! $user = $this->employeeQuery()->find($id)) return errorResponse(message: 'Tài khoản không tồn tại', code: Response::HTTP_NOT_FOUND);

            if (empty($credentials['password'])) {
                unset($credentials['password']);
            } else {
                $credentials['password'] = Hash::make($credentials['password']);
            }

            $user->update($credentials);

            return successResponse(
                message: 'Cập nhật tài khoản nhân viên thành công.',
                data: ['redirect' => '/admin/employees'],
                code: Response::HTTP_OK,
                isToastr: true
            );
        });
    }

    private function validateRequest($request, $id = null)
    {
        $rules = [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'phone'      => ['required', 'string', 'max:15', Rule::unique('users', 'phone')->ignore($id)],
            'address'    => ['nullable', 'string', 'max:255'],
            'storage_id' => [
                'required',
                'integer',
                Rule::exists('storages', 'id')->where(fn ($query) => $query->whereIn('id', $this->managedStorageIds())),
            ],
            'status'     => ['required', 'in:active,inactive,locked'],
            'img_url'    => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'password'   => [$id ? 'nullable' : 'required', 'string', 'min:6'],
        ];

        return $this->validate($request, $rules, __('request.messages'), [
            'name'       => 'Tên tài khoản',
            'email'      => 'Email',
            'phone'      => 'Số điện thoại',
            'password'   => 'Mật khẩu',
            'address'    => 'Địa chỉ',
            'storage_id' => 'Kho hàng',
            'status'     => 'Trạng thái',
            'img_url'    => 'Ảnh đại diện',
        ]);
    }

    private function employeeQuery(): Builder
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
