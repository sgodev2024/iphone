<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if (!session('verify_otp_confirm')) {
            // Nếu session chưa được đặt, chuyển hướng người dùng đến trang login
            return redirect()->route('formlogin')->with('error', 'Please verify your OTP first.');
        }
        return $next($request);
    }

}
