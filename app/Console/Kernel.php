<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustProxies::class,
        // \Illuminate\Http\Middleware\HandleCors::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            // \App\Http\Middleware\EncryptCookies::class,
            // \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            // \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            // \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // \App\Http\Middleware\VerifyCsrfToken::class,
            // \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            // \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $middlewareAliases = [
        // 'auth' => \App\Http\Middleware\Authenticate::class,
        // 'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        // 'cache' => \Illuminate\Http\Middleware\ResponseCache::class,
        // 'can' => \Illuminate\Auth\Middleware\Authorize::class,
        // 'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        // 'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        // 'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Process scheduled responses
        $schedule->command('app:process-scheduled-responses')
                ->everyFiveMinutes()
                ->withoutOverlapping();
        
        // Send session reminders
        $schedule->command('app:send-session-reminders')
                ->dailyAt('08:00')
                ->withoutOverlapping();
        
        // Ensure Reverb WebSocket server is running
        $schedule->command('app:start-reverb --background')
                ->everyFiveMinutes()
                ->withoutOverlapping();
        
        // Update urgent action counts
        $schedule->job(new \App\Jobs\UpdateUrgentActionCounts())
                ->everyFiveMinutes()
                ->withoutOverlapping();
        
        // Process subscription renewals and expirations
        $schedule->command('subscriptions:process-renewals')
                ->daily()
                ->withoutOverlapping();
    }
} 