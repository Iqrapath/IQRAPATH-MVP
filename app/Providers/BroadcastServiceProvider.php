<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Simplified broadcasting routes with minimal middleware
        // This makes authentication easier for WebSockets
        Broadcast::routes(['middleware' => ['auth']]);

        // Register the channel routes
        require base_path('routes/channels.php');
    }
} 