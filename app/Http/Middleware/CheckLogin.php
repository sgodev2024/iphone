<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Closure;
use Illuminate\Http\Request;

class CheckLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        if (!auth()->check()) {
            // Nếu session chưa được đặt, chuyển hướng người dùng đến trang login
            return redirect()->route('auth.login');
        }

        $user = auth()->user();

        if (!in_array($user->role_id, [1, 2, 3])) {
            abort(403, 'Access denied');
        }

        return $next($request);
    }
}
