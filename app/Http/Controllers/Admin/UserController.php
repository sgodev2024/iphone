<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Mail\SendMailInfo;
use App\Models\Roles;
use App\Models\User;
use App\Services\AdminService;
use App\Services\StorageService;
use App\Services\UserService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    protected $userService;
    protected $adminService;
    protected $storageService;
    public function __construct(UserService $userService, AdminService $adminService, StorageService $storageService)
    {
        $this->userService = $userService;
        $this->adminService = $adminService;
        $this->storageService = $storageService;
    }

    public function index(Request $request)
    {
        $title = "Tài khoản quản trị";
        $mode = 'users';
        if ($request->ajax()) {
            $searchText = $request->query('s');

            $users = User::query()
                ->where('id', '<>', Auth::id())
                ->where(['manager_id' => Auth::id(), 'role_id' => 2])
                ->when(!empty($searchText), function ($query) use ($searchText) {
                    $query->where('name', 'like', "%{$searchText}%");
                })
                ->latest()
                ->paginate(10);

            $html = view('admin.employee.table', compact('users', 'mode'))->render();

            return response()->json(['html' => $html]);
        }

        return view('admin.employee.index', compact('title'));
    }

    public function create(Request $request)
    {
        $title = "Thêm chi nhánh";
        $api = '/admin/users';
        $user = null;
        return view('admin.employee.form', compact('title', 'api', 'user'));
    }

    public function store(Request $request)
    {
        // $title = 'Thêm nhân viên';
        // $storage = $this->storageService->getAllStorage();
        // $role    = Roles::all();
        // return view('admin.employee.add', compact('title', 'storage', 'role'));

        $credentials = $this->validateRequest($request);

        return transaction(function () use ($credentials, $request) {
            $credentials['role_id'] = 2;
            $credentials['manager_id'] = Auth::id();

            $user = User::create($credentials);

            Mail::to($credentials['email'])->send(new SendMailInfo($user,  $credentials['password']));

            return successResponse(
                message: 'Tạo tài khoản quản trị thành công.',
                data: ['redirect' => '/admin/users'],
                code: Response::HTTP_CREATED,
                isToastr: true
            );
        });
    }

    public function findByPhone(Request $request)
    {
        try {
            $title = "Nhân viên";
            $staff = $this->adminService->findStaffByPhone($request->input('phone'));
            $user = new LengthAwarePaginator(
                $staff ? [$staff] : [],
                $staff ? 1 : 0,
                10,
                1,
                ['path' => Paginator::resolveCurrentPath()]
            );
            return view('admin.employee.index', compact('user', 'title'));
        } catch (Exception $e) {
            Log::error('Failed to find staff: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to find staff'], 500);
        }
    }
    public function edit(string $id)
    {
        $user = User::query()->where('role_id', 2)->findOrFail($id);
        $title = "Sửa tài khoản - $user->name";
        $api = "/admin/users/$user->id";

        return view('admin.employee.form', compact('title', 'api', 'user'));
    }

    public function update(Request $request, $id)
    {
        $credentials = $this->validateRequest($request, $id);

        return transaction(function () use ($credentials, $id) {

            if (! $user = User::query()->where('role_id', 2)->find($id)) return errorResponse(message: 'Tài khoản không tồn tại', code: Response::HTTP_NOT_FOUND);

            if (empty($credentials['password'])) {
                unset($credentials['password']);
            }

            $user->update($credentials);

            return successResponse(
                message: 'Cập nhật tài khoản quản trị thành công.',
                data: ['redirect' => '/admin/users'],
                code: Response::HTTP_OK,
                isToastr: true
            );
        });
    }

    public function updateadmin(Request $request, $id)
    {
        try {
            $user = $this->adminService->updateUser($id, $request->all());
            $request->session()->regenerate();
            Auth::setUser($user);
            $request->session()->put('authUser', $user);
            return redirect()->route('admin.staff.store')->with('success', 'Cập nhật thành công');
        } catch (Exception $e) {
            Log::error('Failed to fetch products: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch products', 500);
        }
    }

    private function validateRequest($request, $id = null)
    {
        $rules = [
            'name'       => "required|string|max:255",
            'email'      => "required|email|max:255|unique:users,email,{$id}",
            'phone'      => "required|string|max:15|unique:users,phone,{$id}",
            'address'    => "nullable|string|max:255",
            'storage_id' => 'nullable|integer|exists:storages,id',
            'status'     => 'required|in:active,inactive,locked',
            'img_url'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password'   => $id ? 'nullable' : 'required' . "|string|min:6"
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
}
