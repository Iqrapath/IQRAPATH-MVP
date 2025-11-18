<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\OAuthConfigValidator;
use Illuminate\Support\ServiceProvider;

class OAuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(OAuthConfigValidator::class, function ($app) {
            return new OAuthConfigValidator();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Validate OAuth configuration on application boot (only in non-testing environments)
        if (!$this->app->runningInConsole() || $this->app->runningUnitTests()) {
            return;
        }

        try {
            $validator = $this->app->make(OAuthConfigValidator::class);
            $validator->validateAll();
        } catch (\Exception $e) {
            // Don't break the application if validation fails
            \Log::error('OAuth configuration validation failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
