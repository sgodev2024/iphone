<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string $permission
    ): Response {
        $user = Auth::user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
            }

            return redirect()->guest(route('auth.login'));
        }

        if (! $user->hasPermission($permission)) {
            Log::warning('Permission denied', [
                'user_id' => $user->getAuthIdentifier(),
                'role_id' => $user->role_id,
                'role_key' => $user->roleKey(),
                'route' => $request->route()?->getName(),
                'permission' => $permission,
                'reason' => $user->role ? 'missing_permission' : 'missing_role',
            ]);

            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
