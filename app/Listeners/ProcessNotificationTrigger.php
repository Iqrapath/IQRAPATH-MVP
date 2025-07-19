<?php

namespace App\Listeners;

use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessNotificationTrigger implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The notification service instance.
     *
     * @var \App\Services\NotificationService
     */
    protected $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        // Get the event class name
        $eventClass = get_class($event);
        
        // Extract the event name from the class name
        $parts = explode('\\', $eventClass);
        $eventName = end($parts);
        
        Log::info("ProcessNotificationTrigger: Processing event", [
            'event_class' => $eventClass,
            'event_name' => $eventName
        ]);
        
        // Process notification triggers for this event
        $notifications = $this->notificationService->processEventTriggers($eventName, $event);
        
        // Log the number of notifications created
        Log::info("ProcessNotificationTrigger: Processed event {$eventName}", [
            'notifications_count' => count($notifications),
            'notification_ids' => array_map(function($notification) {
                return $notification->id;
            }, $notifications)
        ]);
    }
} 