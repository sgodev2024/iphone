<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Models\UserInfo;
use App\Services\AdminService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    private const DEFAULT_AVATAR = 'avatar/17841016176a573af1aa503.webp';

    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function profile()
    {
        $title = "Thông tin tài khoản";
        $user = Auth::user();
        return view('admin.admin.edit', compact('title', 'user'));
    }

    public function updateProfile(Request $request)
    {
        $userId = Auth::id();

        // Validate dữ liệu
        $credentials = $request->validate(
            [
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($userId),
                ],
                'phone' => [
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('users', 'phone')->ignore($userId),
                ],
                'address' => 'nullable|string|max:255',
                'img_url' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            ],
            __('request.messages'),
            [
                'name' => 'Tên',
                'email' => 'Email',
                'phone' => 'Số điện thoại',
                'address' => 'Địa chỉ',
                'img_url' => 'Ảnh đại diện',
            ]
        );

        return transaction(function () use ($credentials, $request, $userId) {

            $user = User::query()->findOrFail($userId);
            $oldImage = optional($user->user_info)->img_url;
            $newImage = null;

            unset($credentials['img_url']);

            if ($request->hasFile('img_url')) {
                $newImage = uploadImages('img_url', 'avatar');
            }

            $updated = $user->update($credentials);

            if ($updated && $newImage) {
                UserInfo::updateOrCreate(
                    ['user_id' => $user->id],
                    ['img_url' => $newImage]
                );

                if (normalizePublicImagePath($oldImage) !== self::DEFAULT_AVATAR) {
                    deleteImage($oldImage);
                }
            }

            $freshUser = $user->fresh('userInfo');
            Auth::setUser($freshUser);

            return successResponse('Cập nhật hồ sơ thành công.', $freshUser);
        });
    }

    public function changePassword(Request $request)
    {
        if ($request->session()->has('authUser')) {
            $id = session('authUser')->id;
            $result = $this->adminService->changePassword(
                $id,
                $request->password,
                $request->newPassword,
                $request->confirmPassword
            );

            // Return JSON response
            return response()->json($result);
        }

        // Return error if session doesn't have authUser
        return response()->json(['status' => 'error', 'message' => 'Unauthorized access'], 401);
    }
}
