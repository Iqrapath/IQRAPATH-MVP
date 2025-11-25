<?php

namespace App\Providers;

use App\Http\Middleware\NotificationAccessMiddleware;
use App\Services\MessageService;
use App\Services\NotificationService;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the notification service
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });
        
        // Register the message service
        $this->app->singleton(MessageService::class, function ($app) {
            return new MessageService(
                $app->make(NotificationService::class),
                $app->make(\App\Services\AttachmentService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register middleware
        $router = $this->app['router'];
        $router->aliasMiddleware('notification.access', NotificationAccessMiddleware::class);
    }
}
