<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $this->app->singleton(SettingsService::class, function ($app) {
        //     return new SettingsService();
        // });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share auth user ID with all Inertia requests for WebSocket authentication
        Inertia::share([
            'auth.userId' => fn () => Auth::id(),
        ]);
    }
}
