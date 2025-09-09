<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SendMailInfo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $title = "Nhân viên bán hàng";
        $mode = 'employees';
        if ($request->ajax()) {
            $searchText = $request->query('s');

            $users = User::query()
                ->where(['manager_id' => Auth::id(), 'role_id' => 3])
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

    public function create()
    {
        $title = "Thêm nhân viên";
        $api = '/admin/employees';
        $user = null;
        return view('admin.employee.form', compact('title', 'api', 'user'));
    }

    public function store(Request $request)
    {
        $credentials = $this->validateRequest($request);

        return transaction(function () use ($credentials, $request) {
            $credentials['role_id'] = 3;
            $credentials['manager_id'] = Auth::id();

            $user = User::create($credentials);

            Mail::to($credentials['email'])->send(new SendMailInfo($user,  $credentials['password']));

            return successResponse(
                message: 'Tạo tài khoản nhân viên thành công.',
                data: ['redirect' => '/admin/employees'],
                code: Response::HTTP_CREATED,
                isToastr: true
            );
        });
    }

    public function edit(string $id)
    {
        $user = User::query()->where('role_id', 3)->findOrFail($id);
        $title = "Sửa tài khoản nhân viên - $user->name";
        $api = "/admin/employees/$user->id";

        return view('admin.employee.form', compact('title', 'api', 'user'));
    }

    public function update(Request $request, $id)
    {
        $credentials = $this->validateRequest($request, $id);

        return transaction(function () use ($credentials, $id) {

            if (! $user = User::query()->where('role_id', 3)->find($id)) return errorResponse(message: 'Tài khoản không tồn tại', code: Response::HTTP_NOT_FOUND);

            if (empty($credentials['password'])) {
                unset($credentials['password']);
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
