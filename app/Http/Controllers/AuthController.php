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
use Illuminate\Support\Facades\Log;
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

                $redirect = $this->loginRedirectFor($user);

                if ($redirect === null) {
                    Auth::logout();

                    Log::warning('Login denied because the account has no supported role', [
                        'user_id' => $user->getAuthIdentifier(),
                        'role_id' => $user->role_id,
                        'role_key' => $user->roleKey(),
                    ]);

                    return errorResponse('Tài khoản chưa được gán vai trò hợp lệ.', 403);
                }

                return successResponse(
                    'Đăng nhập thành công!',
                    $redirect
                );
            }

            return errorResponse("Mật khẩu không chính xác!", 404);
        });
    }

    private function loginRedirectFor(User $user): ?string
    {
        $fallback = $user->isAdministrator() || $user->isAdminStore()
            ? route('admin.dashboard', absolute: false)
            : ($user->isStaff() ? route('staff.index', absolute: false) : null);

        if ($fallback === null) {
            return null;
        }

        $intended = session()->pull('url.intended');

        if (! is_string($intended) || trim($intended) === '') {
            return $fallback;
        }

        $path = parse_url($intended, PHP_URL_PATH);
        $query = parse_url($intended, PHP_URL_QUERY);

        if (! is_string($path) || ! str_starts_with($path, '/')) {
            return $fallback;
        }

        try {
            $route = app('router')->getRoutes()->match(Request::create($path, 'GET'));
        } catch (\Throwable) {
            return $fallback;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            [$middlewareName, $arguments] = array_pad(explode(':', $middleware, 2), 2, null);
            $middlewareName = trim($middlewareName);
            $arguments = $arguments !== null ? explode(',', $arguments) : [];

            if (($middlewareName === 'role' || str_ends_with($middlewareName, 'RoleMiddleware'))
                && ! $user->hasFullAccess()
                && ! collect($arguments)->contains(fn ($role) => $user->matchesRoleRequirement(trim($role)))) {
                return $fallback;
            }

            if (($middlewareName === 'permission' || str_ends_with($middlewareName, 'PermissionMiddleware'))
                && ! $user->hasPermission((string) ($arguments[0] ?? ''))) {
                return $fallback;
            }
        }

        return url($path . ($query ? '?' . $query : ''));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('auth.login');
    }
}
