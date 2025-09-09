<?php

namespace App\Http\Controllers;

use App\Events\CustomerLogin;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Models\OTP;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function authenticate(LoginRequest $request)
    {
        $credentials = $request->validated();

        return transaction(function () use ($credentials, $request) {

            $remember = $request->filled('remember');

            if (Auth::attempt($credentials, $remember)) {
                $user = Auth::user();

                if ($user->status === 'inactive') {
                    Auth::logout();
                    return errorResponse('Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.', 403);
                }

                if ($user->status === 'locked') {
                    Auth::logout();
                    return errorResponse('Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.', 403);
                }

                $redirect = match ($user->role_id) {
                    1, 2 => '/admin',
                    3 => '/ban-hang',
                    default => abort(403, 'Access denied'),
                };

                return successResponse(
                    'Đăng nhập thành công!',
                    $redirect
                );
            }

            return errorResponse("Mật khẩu không chính xác!", 404);
        });
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('auth.login');
    }
}
