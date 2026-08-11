<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Allow full-access roles through every role group. Other roles must
     * match at least one route argument (ID or role name).
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! auth()->check()) {
            return redirect()->guest(route('auth.login'));
        }

        $user = auth()->user();

        if ($user->hasFullAccess()) {
            return $next($request);
        }

        $hasRequiredRole = $user->role !== null
            && collect($roles)->contains(fn ($role) => $user->matchesRoleRequirement($role));

        if ($hasRequiredRole) {
            return $next($request);
        }

        Log::warning('Role denied', [
            'user_id' => $user->getAuthIdentifier(),
            'role_id' => $user->role_id,
            'role_key' => $user->roleKey(),
            'route' => $request->route()?->getName(),
            'required_roles' => array_values($roles),
            'reason' => $user->role ? 'role_not_allowed' : 'missing_role',
        ]);

        abort(Response::HTTP_FORBIDDEN, 'You do not have access to this area.');
    }
}
