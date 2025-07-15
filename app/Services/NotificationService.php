<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\NotificationTemplate;
use App\Models\NotificationTrigger;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

class NotificationService
{
    /**
     * Create a new notification.
     *
     * @param array $data Notification data
     * @return Notification
     */
    public function createNotification(array $data): Notification
    {
        $notification = Notification::create([
            'title' => $data['title'],
            'body' => $data['body'],
            'type' => $data['type'] ?? 'custom',
            'status' => $data['status'] ?? 'draft',
            'sender_type' => $data['sender_type'] ?? 'system',
            'sender_id' => $data['sender_id'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        return $notification;
    }

    /**
     * Add recipients to a notification.
     *
     * @param Notification $notification
     * @param array $recipientData Array of recipient data
     * @return void
     */
    public function addRecipients(Notification $notification, array $recipientData): void
    {
        $recipients = [];

        // Handle different recipient types
        if (isset($recipientData['all_users']) && $recipientData['all_users']) {
            // Add all users as recipients
            $userIds = User::pluck('id')->toArray();
            foreach ($userIds as $userId) {
                $this->addRecipient($notification, $userId, $recipientData['channels'] ?? ['in-app']);
            }
        } elseif (isset($recipientData['roles']) && !empty($recipientData['roles'])) {
            // Add users with specific roles
            $userIds = User::whereIn('role', $recipientData['roles'])->pluck('id')->toArray();
            foreach ($userIds as $userId) {
                $this->addRecipient($notification, $userId, $recipientData['channels'] ?? ['in-app']);
            }
        } elseif (isset($recipientData['user_ids']) && !empty($recipientData['user_ids'])) {
            // Add specific users
            foreach ($recipientData['user_ids'] as $userId) {
                $this->addRecipient($notification, $userId, $recipientData['channels'] ?? ['in-app']);
            }
        }
    }

    /**
     * Add a single recipient to a notification.
     *
     * @param Notification $notification
     * @param int $userId
     * @param array $channels
     * @return void
     */
    public function addRecipient(Notification $notification, int $userId, array $channels = ['in-app']): void
    {
        foreach ($channels as $channel) {
            // Check if recipient already exists for this channel
            $exists = NotificationRecipient::where('notification_id', $notification->id)
                ->where('user_id', $userId)
                ->where('channel', $channel)
                ->exists();

            if (!$exists) {
                NotificationRecipient::create([
                    'notification_id' => $notification->id,
                    'user_id' => $userId,
                    'channel' => $channel,
                    'status' => 'pending',
                ]);
            }
        }
    }

    /**
     * Send a notification.
     *
     * @param Notification $notification
     * @return bool
     */
    public function sendNotification(Notification $notification): bool
    {
        try {
            // Check if notification is scheduled for future
            if ($notification->scheduled_at && $notification->scheduled_at->isFuture()) {
                $notification->status = 'scheduled';
                $notification->save();
                return true;
            }

            // Set notification as sent
            $notification->status = 'sent';
            $notification->sent_at = now();
            $notification->save();

            // Process recipients
            $recipients = $notification->recipients;
            foreach ($recipients as $recipient) {
                $this->deliverToRecipient($notification, $recipient);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send notification: ' . $e->getMessage(), [
                'notification_id' => $notification->id,
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * Deliver notification to a specific recipient.
     *
     * @param Notification $notification
     * @param NotificationRecipient $recipient
     * @return bool
     */
    protected function deliverToRecipient(Notification $notification, NotificationRecipient $recipient): bool
    {
        try {
            $user = $recipient->user;
            if (!$user) {
                $recipient->status = 'failed';
                $recipient->save();
                return false;
            }

            switch ($recipient->channel) {
                case 'in-app':
                    // In-app notifications are just marked as delivered
                    $recipient->status = 'delivered';
                    $recipient->delivered_at = now();
                    break;

                case 'email':
                    // Queue email delivery
                    Queue::push(function () use ($notification, $user, $recipient) {
                        try {
                            Mail::to($user->email)->send(new \App\Mail\NotificationMail($notification));
                            $recipient->status = 'delivered';
                            $recipient->delivered_at = now();
                            $recipient->save();
                        } catch (\Exception $e) {
                            Log::error('Failed to send email notification: ' . $e->getMessage(), [
                                'recipient_id' => $recipient->id,
                                'user_id' => $user->id,
                                'exception' => $e,
                            ]);
                            $recipient->status = 'failed';
                            $recipient->save();
                        }
                    });
                    $recipient->status = 'sent';
                    break;

                case 'sms':
                    // Implementation for SMS would go here
                    // For now, we'll just mark it as sent
                    $recipient->status = 'sent';
                    break;

                default:
                    $recipient->status = 'failed';
                    break;
            }

            $recipient->save();
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to deliver notification: ' . $e->getMessage(), [
                'recipient_id' => $recipient->id,
                'exception' => $e,
            ]);
            
            $recipient->status = 'failed';
            $recipient->save();
            return false;
        }
    }

    /**
     * Process a notification trigger for an event.
     *
     * @param string $eventName
     * @param array $eventData
     * @return Notification|null
     */
    public function processEvent(string $eventName, array $eventData = []): ?Notification
    {
        // Find triggers for this event
        $triggers = NotificationTrigger::enabled()->forEvent($eventName)->get();
        
        if ($triggers->isEmpty()) {
            return null;
        }
        
        // Process the first matching trigger
        $trigger = $triggers->first();
        return $trigger->process($eventData);
    }

    /**
     * Send scheduled notifications that are due.
     *
     * @return int Number of notifications sent
     */
    public function sendScheduledNotifications(): int
    {
        $count = 0;
        $now = now();
        
        Log::info('Checking for scheduled notifications...', ['time' => $now->toDateTimeString()]);
        
        try {
            // Get scheduled notifications that are due
            $notifications = Notification::where('status', 'scheduled')
                ->where('scheduled_at', '<=', $now)
                ->get();
            
            Log::info('Found scheduled notifications', ['count' => $notifications->count()]);
                
            foreach ($notifications as $notification) {
                try {
                    Log::info('Sending scheduled notification', [
                        'notification_id' => $notification->id,
                        'title' => $notification->title,
                        'scheduled_at' => $notification->scheduled_at
                    ]);
                    
                    // Update status to sent before sending
                    $notification->status = 'sent';
                    $notification->sent_at = now();
                    $notification->save();
                    
                    // Process recipients
                    $recipients = $notification->recipients;
                    foreach ($recipients as $recipient) {
                        $this->deliverToRecipient($notification, $recipient);
                    }
                    
                    $count++;
                    Log::info('Successfully sent scheduled notification', [
                        'notification_id' => $notification->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error sending scheduled notification: ' . $e->getMessage(), [
                        'notification_id' => $notification->id,
                        'exception' => $e
                    ]);
                    
                    // Revert status to scheduled if failed
                    $notification->status = 'scheduled';
                    $notification->save();
                }
            }
            
            Log::info('Completed sending scheduled notifications', [
                'total_processed' => $notifications->count(),
                'successfully_sent' => $count
            ]);
            
            return $count;
        } catch (\Exception $e) {
            Log::error('Error processing scheduled notifications: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return 0;
        }
    }

    /**
     * Create a notification from a template.
     *
     * @param string $templateName
     * @param array $data
     * @param array $recipientData
     * @return Notification|null
     */
    public function createFromTemplate(string $templateName, array $data = [], array $recipientData = []): ?Notification
    {
        $template = NotificationTemplate::where('name', $templateName)
            ->where('is_active', true)
            ->first();
            
        if (!$template) {
            return null;
        }
        
        $notification = $template->createNotification($data);
        
        if (!empty($recipientData)) {
            $this->addRecipients($notification, $recipientData);
        }
        
        return $notification;
    }

    /**
     * Get unread notifications count for a user.
     *
     * @param int $userId
     * @param string $channel
     * @return int
     */
    public function getUnreadCount(int $userId, string $channel = 'in-app'): int
    {
        return NotificationRecipient::where('user_id', $userId)
            ->where('channel', $channel)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Mark a notification as read for a user.
     *
     * @param int $notificationId
     * @param int $userId
     * @param string $channel
     * @return bool
     */
    public function markAsRead(int $notificationId, int $userId, string $channel = 'in-app'): bool
    {
        $recipient = NotificationRecipient::where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->where('channel', $channel)
            ->first();
            
        if (!$recipient) {
            return false;
        }
        
        $recipient->markAsRead();
        return true;
    }
} 