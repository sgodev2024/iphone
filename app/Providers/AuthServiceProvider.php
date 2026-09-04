<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register authentication and authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function (User $user, string $ability) {
            // hasFullAccess() is intentionally restricted to Administrator.
            if ($user->hasFullAccess()) {
                return true;
            }

            return $user->hasPermission($ability) ? true : null;
        });

        Gate::after(function (User $user, string $ability, ?bool $result): void {
            if ($result === false) {
                Log::warning('Gate authorization denied', [
                    'user_id' => $user->getAuthIdentifier(),
                    'role_id' => $user->role_id,
                    'role_key' => $user->roleKey(),
                    'route' => request()->route()?->getName(),
                    'permission' => $ability,
                    'reason' => $user->role ? 'missing_permission' : 'missing_role',
                ]);
            }
        });
    }
}
