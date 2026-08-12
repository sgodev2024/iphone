<?php

namespace App\Providers;

use App\Http\View\Composers\NotificationComposer;
use App\Models\Config;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Observers\TransactionEntryObserver;
use App\Observers\TransactionObserver;
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
        Transaction::observe(TransactionObserver::class);
        TransactionEntry::observe(TransactionEntryObserver::class);

        View::composer('admin.layout.header', NotificationComposer::class);

        // Chia sẻ $config cho tất cả view
        View::composer('*', function ($view) {
            $config = null;

            if (Auth::check()) {
                $user = Auth::user();
                $managerId = $user->manager_id ?: null;

                $config = Config::with(['bank', 'user'])
                    ->where('user_id', $user->id)
                    ->first();

                if (!$config && $managerId) {
                    $config = Config::with(['bank', 'user'])
                        ->where('user_id', $managerId)
                        ->first();
                }
            }

            $config ??= Config::with(['bank', 'user'])->first();

            $view->with('config', $config);
        });

        // Set locale cho Carbon
        Carbon::setLocale('vi');
    }
}
