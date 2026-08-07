<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Mail\SendMailInfo;
use App\Models\User;
use App\Services\AdminService;
use App\Services\StorageService;
use App\Services\UserService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UserController extends Controller
{
    private const BRANCH_ROLE_ID = 2;

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
        $title = 'Tai khoan quan tri';
        $mode = 'users';

        if ($request->ajax()) {
            $searchText = $request->query('s');

            $users = User::query()
                ->where('id', '<>', Auth::id())
                ->where('manager_id', Auth::id())
                ->where('role_id', self::BRANCH_ROLE_ID)
                ->when(! empty($searchText), function ($query) use ($searchText) {
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
        $title = 'Them chi nhanh';
        $api = '/admin/users';
        $user = null;

        return view('admin.employee.form', compact('title', 'api', 'user'));
    }

    public function store(Request $request)
    {
        $credentials = $this->validateRequest($request);
        $plainPassword = $credentials['password'];

        try {
            $user = DB::transaction(function () use ($credentials) {
                $credentials['role_id'] = self::BRANCH_ROLE_ID;
                $credentials['manager_id'] = Auth::id();
                $credentials['password'] = Hash::make($credentials['password']);

                return User::create($credentials);
            });
        } catch (Throwable $e) {
            Log::error('Failed to create branch account.', [
                'manager_id' => Auth::id(),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return errorResponse('Khong the tao tai khoan chi nhanh. Chi tiet loi da duoc ghi vao log.');
        }

        $this->sendAccountInfoEmail($user, $plainPassword);

        return successResponse(
            message: 'Tao tai khoan chi nhanh thanh cong.',
            data: ['redirect' => '/admin/users'],
            code: Response::HTTP_CREATED,
            isToastr: true
        );
    }

    public function findByPhone(Request $request)
    {
        try {
            $title = 'Nhan vien';
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
        $user = User::query()
            ->where('role_id', self::BRANCH_ROLE_ID)
            ->where('manager_id', Auth::id())
            ->findOrFail($id);
        $title = "Sua tai khoan - {$user->name}";
        $api = "/admin/users/{$user->id}";

        return view('admin.employee.form', compact('title', 'api', 'user'));
    }

    public function update(Request $request, $id)
    {
        $credentials = $this->validateRequest($request, $id);

        return transaction(function () use ($credentials, $id) {
            $user = User::query()
                ->where('role_id', self::BRANCH_ROLE_ID)
                ->where('manager_id', Auth::id())
                ->find($id);

            if (! $user) {
                return errorResponse(message: 'Tai khoan khong ton tai', code: Response::HTTP_NOT_FOUND);
            }

            if (empty($credentials['password'])) {
                unset($credentials['password']);
            } else {
                $credentials['password'] = Hash::make($credentials['password']);
            }

            $user->update($credentials);

            return successResponse(
                message: 'Cap nhat tai khoan chi nhanh thanh cong.',
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

            return redirect()->route('admin.staff.store')->with('success', 'Cap nhat thanh cong');
        } catch (Exception $e) {
            Log::error('Failed to fetch products: ' . $e->getMessage());

            return ApiResponse::error('Failed to fetch products', 500);
        }
    }

    private function sendAccountInfoEmail(User $user, string $plainPassword): void
    {
        try {
            Mail::to($user->email)->send(new SendMailInfo($user, $plainPassword));
        } catch (Throwable $e) {
            Log::error('Failed to send branch account email.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    private function validateRequest($request, $id = null)
    {
        $rules = [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'phone'      => ['required', 'string', 'max:15', Rule::unique('users', 'phone')->ignore($id)],
            'address'    => ['nullable', 'string', 'max:255'],
            'storage_id' => ['nullable', 'integer', 'exists:storages,id'],
            'status'     => ['required', 'in:active,inactive,locked'],
            'img_url'    => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'password'   => [$id ? 'nullable' : 'required', 'string', 'min:6'],
        ];

        return $this->validate($request, $rules, __('request.messages'), [
            'name'       => 'Ten tai khoan',
            'email'      => 'Email',
            'phone'      => 'So dien thoai',
            'password'   => 'Mat khau',
            'address'    => 'Dia chi',
            'storage_id' => 'Kho hang',
            'status'     => 'Trang thai',
            'img_url'    => 'Anh dai dien',
        ]);
    }
}
