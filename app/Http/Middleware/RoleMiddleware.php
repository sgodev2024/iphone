<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!auth()->check()) {
            return redirect()->route('auth.login');
        }

        $user = auth()->user();

        // Admin (role_id = 1) => đi được tất cả
        if ($user->role_id == 1 || $user->role_id == 2) {
            return $next($request);
        }

        // Nếu user có role nằm trong danh sách middleware cho phép
        if (in_array($user->role_id, $roles)) {
            return $next($request);
        }

        // Nếu không hợp lệ => 403
        abort(403, 'Không có quyền truy cập');
    }
}
