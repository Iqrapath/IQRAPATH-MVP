<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\StudentProfile;
use App\Observers\StudentProfileObserver;

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
        // Register model observers
        StudentProfile::observe(StudentProfileObserver::class);

        // Share auth user ID with all Inertia requests for WebSocket authentication
        Inertia::share([
            'auth.userId' => fn () => Auth::id(),
        ]);

        // Fix incorrectly stored morph types in notifications (legacy data)
        Relation::morphMap([
            // Correct alias
            'user' => \App\Models\User::class,
            // Ensure FQCN resolves correctly
            'App\\Models\\User' => \App\Models\User::class,
            // Legacy incorrect values accidentally saved from controller namespace resolution
            'App\\Http\\Controllers\\User' => \App\Models\User::class,
            'App\\Http\\Controllers\\Admin\\User' => \App\Models\User::class,
        ]);
    }
}
