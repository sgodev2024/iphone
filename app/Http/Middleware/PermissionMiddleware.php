<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class PermissionMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string $permission
    ): Response {
        return $next($request);

        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        $permissions = $user->role
                            ->permissions
                            ->pluck('permission_key');
        
        if (!$permissions->contains($permission)) {
            abort(403);
        }

        return $next($request);
    }
}