<?php

namespace App\Providers;

use App\Http\View\Composers\NotificationComposer;
use App\Models\Config;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('admin.layout.header', NotificationComposer::class);

        // Chia sẻ $config cho tất cả view
        View::composer('*', function ($view) {
            $config = Config::first();
            $view->with('config', $config);
        });

        // Set locale cho Carbon
        Carbon::setLocale('vi');
    }
}
