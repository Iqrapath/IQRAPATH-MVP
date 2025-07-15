<?php

namespace App\Events;

use App\Models\NotificationRecipient;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The notification recipient instance.
     *
     * @var \App\Models\NotificationRecipient
     */
    public $notificationRecipient;
    
    /**
     * The notification data to broadcast.
     *
     * @var array
     */
    public $notificationData;

    /**
     * Create a new event instance.
     */
    public function __construct(NotificationRecipient $notificationRecipient)
    {
        $this->notificationRecipient = $notificationRecipient;
        
        // Format the notification data for the frontend
        $this->notificationData = [
            'id' => $notificationRecipient->id,
            'title' => $notificationRecipient->notification ? $notificationRecipient->notification->title : 'No title',
            'body' => $notificationRecipient->notification ? $notificationRecipient->notification->body : 'No body',
            'type' => $notificationRecipient->notification ? $notificationRecipient->notification->type : 'system',
            'created_at' => $notificationRecipient->created_at,
            'read_at' => $notificationRecipient->read_at,
            'status' => $notificationRecipient->status,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('notifications.' . $this->notificationRecipient->user_id),
        ];
    }
    
    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'notification' => $this->notificationData,
        ];
    }
    
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'notification.received';
    }
} 