<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
            /*
             * Chỉ role 1 là Super Admin.
             */
            if ( $user->role->name === 'admin' ) {
                return true;
            }

            /*
             * Kiểm tra ability, ví dụ product.view,
             * có được gán cho role của user hay không.
             */
            $hasPermission = $user->role()
                ->whereHas('permissions', function ($query) use ($ability) {
                    $query->where('permission_key', $ability);
                })
                ->exists();

            /*
             * Có quyền thì cho phép.
             *
             * Không có quyền trả về null để Laravel tiếp tục
             * quá trình kiểm tra authorization.
             */
            return $hasPermission ? true : null;
        });
    }
}