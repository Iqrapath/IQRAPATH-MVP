<?php

namespace App\Models;

use App\Events\NotificationReceived;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRecipient extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'notification_id',
        'user_id',
        'status',
        'channel',
        'delivered_at',
        'read_at',
        'personalized_content',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'personalized_content' => 'array',
    ];
    
    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => NotificationReceived::class,
    ];

    /**
     * Get the notification associated with the recipient.
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Get the user associated with the recipient.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the personalized title for this notification recipient.
     */
    public function getPersonalizedTitleAttribute()
    {
        if (isset($this->personalized_content['title'])) {
            return $this->personalized_content['title'];
        }
        
        return $this->notification ? $this->notification->title : '';
    }
    
    /**
     * Get the personalized body for this notification recipient.
     */
    public function getPersonalizedBodyAttribute()
    {
        if (isset($this->personalized_content['body'])) {
            return $this->personalized_content['body'];
        }
        
        return $this->notification ? $this->notification->body : '';
    }

    /**
     * Mark the notification as delivered.
     */
    public function markAsDelivered()
    {
        $this->status = 'delivered';
        $this->delivered_at = now();
        $this->save();
        
        return $this;
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead()
    {
        $this->status = 'read';
        $this->read_at = now();
        $this->save();
        
        return $this;
    }

    /**
     * Scope a query to only include notifications for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include notifications for a specific channel.
     */
    public function scopeForChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope a query to only include unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
} 