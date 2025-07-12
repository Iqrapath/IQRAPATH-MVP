<?php

namespace App\Providers;

use App\Events\PaymentProcessed;
use App\Events\SessionScheduled;
use App\Events\SubscriptionExpiring;
use App\Events\UserRegistered;
use App\Listeners\ProcessNotificationTrigger;
use App\Listeners\SendPaymentConfirmation;
use App\Listeners\SendSessionReminder;
use App\Listeners\SendSubscriptionExpiryReminder;
use App\Listeners\SendWelcomeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        // User registration event
        UserRegistered::class => [
            SendWelcomeNotification::class,
            ProcessNotificationTrigger::class,
        ],
        
        // Payment events
        PaymentProcessed::class => [
            SendPaymentConfirmation::class,
            ProcessNotificationTrigger::class,
        ],
        
        // Session events
        SessionScheduled::class => [
            SendSessionReminder::class,
            ProcessNotificationTrigger::class,
        ],
        
        // Subscription events
        SubscriptionExpiring::class => [
            SendSubscriptionExpiryReminder::class,
            ProcessNotificationTrigger::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
} 