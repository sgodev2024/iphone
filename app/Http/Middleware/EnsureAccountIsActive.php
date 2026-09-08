<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAccountIsActive
{
    private const INACTIVE_MESSAGE = 'Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.';

    private const LOCKED_MESSAGE = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.';

    public function handle(Request $request, Closure $next): Response
    {
        $authenticatedUser = Auth::user();

        if (! $authenticatedUser) {
            return $next($request);
        }

        $currentStatus = User::query()
            ->whereKey($authenticatedUser->getAuthIdentifier())
            ->value('status');

        if ($currentStatus === 'active') {
            return $next($request);
        }

        $message = $currentStatus === 'locked'
            ? self::LOCKED_MESSAGE
            : self::INACTIVE_MESSAGE;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->ajax()) {
            return errorResponse($message, Response::HTTP_FORBIDDEN);
        }

        return redirect()
            ->route('auth.login')
            ->with('error', $message);
    }
}
