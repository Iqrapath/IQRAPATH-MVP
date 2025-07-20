<?php

namespace App\Providers;

use App\Events\PaymentProcessed;
use App\Events\SessionScheduled;
use App\Events\SubscriptionExpiring;
use App\Events\UserRegistered;
use App\Events\UserRoleAssigned;
use App\Events\UserAccountUpdated;
use App\Listeners\AccountRoleAssignedNotification;
use App\Listeners\AccountUpdatedNotification;
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
        
        // User role assignment event
        UserRoleAssigned::class => [
            AccountRoleAssignedNotification::class,
            ProcessNotificationTrigger::class,
        ],
        
        // User account update event
        UserAccountUpdated::class => [
            AccountUpdatedNotification::class,
            ProcessNotificationTrigger::class,
        ],
        
        // Notification created event (for broadcasting)
        \App\Events\NotificationCreated::class => [],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Register observers
        \App\Models\PayoutRequest::observe(\App\Observers\PayoutRequestObserver::class);
        \App\Models\Dispute::observe(\App\Observers\DisputeObserver::class);
        \App\Models\VerificationRequest::observe(\App\Observers\VerificationRequestObserver::class);
        \App\Models\TeachingSession::observe(\App\Observers\TeachingSessionObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
} 