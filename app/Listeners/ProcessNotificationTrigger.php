<?php

namespace App\Listeners;

use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
        // Determine the event name based on the event class
        $eventName = $this->getEventName($event);
        
        // Extract event data
        $eventData = $this->extractEventData($event);
        
        // Process notification triggers for this event
        $this->notificationService->processEvent($eventName, $eventData);
    }

    /**
     * Get the event name from the event class.
     */
    protected function getEventName($event): string
    {
        // Get the class name without namespace
        $className = class_basename($event);
        
        // Convert from camel case to snake case with dots
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '.$0', $className));
    }

    /**
     * Extract relevant data from the event.
     */
    protected function extractEventData($event): array
    {
        $data = [];
        
        // Extract common properties from events
        if (isset($event->user)) {
            $data['user_id'] = $event->user->id;
            $data['user_name'] = $event->user->name;
            $data['user_email'] = $event->user->email;
        }
        
        // Handle specific event types
        switch (true) {
            case method_exists($event, 'getPayment'):
                $payment = $event->getPayment();
                $data['payment_id'] = $payment->id;
                $data['amount'] = $payment->amount;
                $data['currency'] = $payment->currency;
                $data['status'] = $payment->status;
                break;
                
            case method_exists($event, 'getSession'):
                $session = $event->getSession();
                $data['session_id'] = $session->id;
                $data['session_title'] = $session->title;
                $data['session_start_time'] = $session->start_time;
                $data['teacher_id'] = $session->teacher_id;
                $data['student_id'] = $session->student_id;
                break;
                
            case method_exists($event, 'getSubscription'):
                $subscription = $event->getSubscription();
                $data['subscription_id'] = $subscription->id;
                $data['plan_name'] = $subscription->plan_name;
                $data['expires_at'] = $subscription->expires_at;
                break;
        }
        
        return $data;
    }
} 