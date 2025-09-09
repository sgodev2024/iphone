<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckLoginSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (!session('authSuper')) {
            // Nếu session chưa được đặt, chuyển hướng người dùng đến trang login
            return redirect()->route('super.dang.nhap');
        }
        return $next($request);
    }
}
