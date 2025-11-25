<?php

use App\Http\Middleware\ApplySystemSettings;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckTeacherVerification;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectBasedOnRole;
use App\Http\Middleware\TrackUserActivity;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->group(base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);


        $middleware->web(append: [
            ApplySystemSettings::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            TrackUserActivity::class,
            RedirectBasedOnRole::class,
        ]);
        
        // Add Sanctum stateful API middleware
        $middleware->statefulApi();

        $middleware->alias([
            'role' => CheckRole::class,
            'teacher.verified' => CheckTeacherVerification::class,
            'verify.stripe' => \App\Http\Middleware\VerifyStripeSignature::class,
            'verify.paystack' => \App\Http\Middleware\VerifyPaystackSignature::class,
            'verify.paypal' => \App\Http\Middleware\VerifyPayPalSignature::class,
            'throttle.oauth' => \App\Http\Middleware\ThrottleOAuthRequests::class,
            'attachment.quota' => \App\Http\Middleware\CheckAttachmentQuota::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
