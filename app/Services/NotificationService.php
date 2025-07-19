<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\NotificationTrigger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Get all notifications for a user
     *
     * @param User $user
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserNotifications(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Notification::where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get unread notifications for a user
     *
     * @param User $user
     * @return Collection
     */
    public function getUnreadNotifications(User $user): Collection
    {
        return Notification::where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get unread notification count for a user
     *
     * @param User $user
     * @return int
     */
    public function getUnreadNotificationCount(User $user): int
    {
        return Notification::where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Create a notification for a user
     *
     * @param User $recipient
     * @param string $type
     * @param array $data
     * @param string $level
     * @return Notification
     */
    public function createNotification(User $recipient, string $type, array $data, string $level = 'info'): Notification
    {
        $notification = new Notification();
        $notification->id = (string) Str::uuid();
        $notification->type = $type;
        $notification->notifiable_type = User::class;
        $notification->notifiable_id = $recipient->id;
        $notification->level = $level;
        $notification->data = $data;
        $notification->save();
        
        return $notification;
    }

    /**
     * Create a notification from a template
     *
     * @param string $templateName
     * @param array $data
     * @param array $options
     * @return Notification|null
     */
    public function createFromTemplate(string $templateName, array $data, array $options = []): ?Notification
    {
        // Get the template
        $template = NotificationTemplate::where('name', $templateName)
            ->active()
            ->first();
            
        if (!$template) {
            return null;
        }
        
        // Replace placeholders in the template
        $notificationData = $template->replacePlaceholders($data);
        
        // Get user IDs from options
        $userIds = $options['user_ids'] ?? [];
        
        // If no user IDs are provided, return null
        if (empty($userIds)) {
            return null;
        }
        
        // Create notifications for each user
        $notifications = [];
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $notification = $this->createNotification(
                    $user,
                    $template->type,
                    $notificationData,
                    $template->level
                );
                $notifications[] = $notification;
            }
        }
        
        // Return the first notification (or null if none were created)
        return $notifications[0] ?? null;
    }

    /**
     * Process notification triggers for an event
     *
     * @param string $eventName
     * @param object $event
     * @return array
     */
    public function processEventTriggers(string $eventName, object $event): array
    {
        // Get all enabled triggers for this event
        $triggers = NotificationTrigger::enabled()
            ->forEvent($eventName)
            ->get();
            
        Log::info("NotificationService: Processing event triggers", [
            'event_name' => $eventName,
            'triggers_found' => $triggers->count(),
            'trigger_ids' => $triggers->pluck('id')->toArray()
        ]);
        
        $notifications = [];
        
        foreach ($triggers as $trigger) {
            Log::info("NotificationService: Processing trigger", [
                'trigger_id' => $trigger->id,
                'trigger_name' => $trigger->name,
                'template_name' => $trigger->template_name,
                'has_direct_content' => !empty($trigger->title) && !empty($trigger->body)
            ]);
            
            // If using a template
            if ($trigger->template_name) {
                // Get data from the event based on the event type
                $data = $this->extractDataFromEvent($event);
                
                // Get users based on audience type
                $userIds = $this->getUsersForTrigger($trigger, $event);
                
                Log::info("NotificationService: Using template", [
                    'template_name' => $trigger->template_name,
                    'user_ids' => $userIds,
                    'data_keys' => array_keys($data)
                ]);
                
                // Create notification from template
                $notification = $this->createFromTemplate(
                    $trigger->template_name,
                    $data,
                    [
                        'user_ids' => $userIds,
                        'channels' => $trigger->channels,
                    ]
                );
                
                if ($notification) {
                    $notifications[] = $notification;
                    Log::info("NotificationService: Created notification from template", [
                        'notification_id' => $notification->id,
                        'template_name' => $trigger->template_name
                    ]);
                } else {
                    Log::warning("NotificationService: Failed to create notification from template", [
                        'template_name' => $trigger->template_name
                    ]);
                }
            } 
            // If using direct title/body
            else if ($trigger->title && $trigger->body) {
                // Get users based on audience type
                $userIds = $this->getUsersForTrigger($trigger, $event);
                
                Log::info("NotificationService: Using direct content", [
                    'user_ids' => $userIds
                ]);
                
                foreach ($userIds as $userId) {
                    $user = User::find($userId);
                    if ($user) {
                        $notification = $this->createNotification(
                            $user,
                            'trigger_' . $trigger->id,
                            [
                                'title' => $trigger->title,
                                'body' => $trigger->body,
                                'action_text' => $trigger->action_text ?? null,
                                'action_url' => $trigger->action_url ?? null,
                            ],
                            $trigger->level
                        );
                        
                        $notifications[] = $notification;
                        Log::info("NotificationService: Created notification with direct content", [
                            'notification_id' => $notification->id,
                            'user_id' => $user->id
                        ]);
                    } else {
                        Log::warning("NotificationService: User not found", [
                            'user_id' => $userId
                        ]);
                    }
                }
            }
        }
        
        Log::info("NotificationService: Finished processing triggers", [
            'event_name' => $eventName,
            'notifications_created' => count($notifications)
        ]);
        
        return $notifications;
    }
    
    /**
     * Extract data from an event based on its type
     *
     * @param object $event
     * @return array
     */
    protected function extractDataFromEvent(object $event): array
    {
        $data = [];
        
        // Extract common data
        if (isset($event->user)) {
            $data['User_Name'] = $event->user->name;
            $data['User_Email'] = $event->user->email;
        }
        
        // Extract specific data based on event type
        $className = get_class($event);
        
        switch ($className) {
            case 'App\Events\UserRegistered':
                // User registration specific data
                break;
                
            case 'App\Events\PaymentProcessed':
                $payment = $event->getPayment();
                $data['Amount'] = $payment['amount'] ?? '0.00';
                $data['Currency'] = $payment['currency'] ?? 'USD';
                $data['Date'] = now()->format('F j, Y');
                $data['Plan_Name'] = $payment['plan_name'] ?? 'Subscription';
                break;
                
            case 'App\Events\SessionScheduled':
                $session = $event->getSession();
                $data['Session_Title'] = $session->title ?? 'Your Class';
                $data['Session_Time'] = $session->start_time 
                    ? date('F j, Y g:i A', strtotime($session->start_time))
                    : 'Scheduled Time';
                $data['Teacher_Name'] = $session->teacher_profile->name ?? 'Your Teacher';
                break;
                
            case 'App\Events\SubscriptionExpiring':
                $subscription = $event->getSubscription();
                $data['Plan_Name'] = $subscription->plan_name ?? 'Your Subscription';
                $data['Expiry_Date'] = $subscription->expires_at 
                    ? date('F j, Y', strtotime($subscription->expires_at))
                    : 'Soon';
                $data['Days_Remaining'] = $subscription->expires_at 
                    ? ceil((strtotime($subscription->expires_at) - time()) / 86400)
                    : '3';
                break;
        }
        
        return $data;
    }
    
    /**
     * Get user IDs for a trigger based on audience type
     *
     * @param NotificationTrigger $trigger
     * @param object $event
     * @return array
     */
    protected function getUsersForTrigger(NotificationTrigger $trigger, object $event): array
    {
        $userIds = [];
        
        // If the event has a user property, always include that user
        if (isset($event->user)) {
            $userIds[] = $event->user->id;
        }
        
        // If audience is all, get all active users
        if ($trigger->audience_type === 'all') {
            $userIds = User::where('status_type', 'active')->pluck('id')->toArray();
        }
        
        // If audience is role-based, get users with those roles
        else if ($trigger->audience_type === 'role') {
            $roles = $trigger->audience_filter['roles'] ?? [];
            if (!empty($roles)) {
                $roleUsers = User::whereIn('role', $roles)
                    ->where('status_type', 'active')
                    ->pluck('id')
                    ->toArray();
                $userIds = array_merge($userIds, $roleUsers);
            }
        }
        
        // If audience is individual, get those specific users
        else if ($trigger->audience_type === 'individual') {
            $individualIds = $trigger->audience_filter['user_ids'] ?? [];
            if (!empty($individualIds)) {
                $userIds = array_merge($userIds, $individualIds);
            }
        }
        
        // Remove duplicates
        return array_unique($userIds);
    }

    /**
     * Mark a notification as read
     *
     * @param Notification $notification
     * @return bool
     */
    public function markAsRead(Notification $notification): bool
    {
        return $notification->markAsRead()->save();
    }

    /**
     * Mark all notifications as read for a user
     *
     * @param User $user
     * @return bool
     */
    public function markAllAsRead(User $user): bool
    {
        return Notification::where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Delete a notification
     *
     * @param Notification $notification
     * @return bool
     */
    public function deleteNotification(Notification $notification): bool
    {
        return $notification->delete();
    }
} 