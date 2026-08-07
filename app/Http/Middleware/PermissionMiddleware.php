<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            abort(401);
        }

        $role = $user->role;

        if (! $role) {
            abort(403);
        }

        $permissions = $role->permissions()
            ->pluck('permission_key');

        if (! $permissions->contains($permission)) {
            abort(403);
        }

        return $next($request);
    }
}
