<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTrigger extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'event',
        'template_id',
        'audience_type',
        'audience_filter',
        'channels',
        'timing_type',
        'timing_value',
        'timing_unit',
        'is_enabled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'audience_filter' => 'array',
        'channels' => 'array',
        'is_enabled' => 'boolean',
    ];

    /**
     * Get the template associated with the trigger.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    /**
     * Scope a query to only include enabled triggers.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope a query to only include triggers for a specific event.
     */
    public function scopeForEvent($query, $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Process this trigger with the given event data.
     */
    public function process(array $eventData = []): ?Notification
    {
        if (!$this->is_enabled) {
            return null;
        }

        // Get the template
        $template = $this->template;
        if (!$template || !$template->is_active) {
            return null;
        }

        // Create the notification from the template
        $notification = $template->createNotification($eventData);
        
        // Set scheduled time if needed
        if ($this->timing_type !== 'immediate') {
            $notification->scheduled_at = $this->calculateScheduledTime($eventData);
            $notification->status = 'scheduled';
        }

        $notification->save();

        // Determine recipients based on audience type
        $userIds = $this->determineRecipients($eventData);
        
        // Create notification recipients
        foreach ($userIds as $userId) {
            foreach ($this->channels as $channel) {
                NotificationRecipient::create([
                    'notification_id' => $notification->id,
                    'user_id' => $userId,
                    'channel' => $channel,
                    'status' => 'pending',
                ]);
            }
        }

        // Send immediately if needed
        if ($this->timing_type === 'immediate') {
            $notification->send();
        }

        return $notification;
    }

    /**
     * Calculate the scheduled time based on timing settings.
     */
    protected function calculateScheduledTime(array $eventData = [])
    {
        $now = now();
        
        // If event data contains a specific date to calculate from
        $baseTime = isset($eventData['event_time']) ? 
            \Carbon\Carbon::parse($eventData['event_time']) : $now;
            
        if ($this->timing_type === 'before_event') {
            return $baseTime->sub($this->timing_value, $this->timing_unit);
        } elseif ($this->timing_type === 'after_event') {
            return $baseTime->add($this->timing_value, $this->timing_unit);
        }
        
        return $now;
    }

    /**
     * Determine the recipients based on audience type and filter.
     */
    protected function determineRecipients(array $eventData = []): array
    {
        // If event data contains specific user IDs
        if (isset($eventData['user_id'])) {
            return [$eventData['user_id']];
        }
        
        if (isset($eventData['user_ids']) && is_array($eventData['user_ids'])) {
            return $eventData['user_ids'];
        }
        
        // Determine by audience type
        switch ($this->audience_type) {
            case 'all':
                return User::pluck('id')->toArray();
                
            case 'role':
                $roles = $this->audience_filter['roles'] ?? [];
                return User::whereIn('role', $roles)->pluck('id')->toArray();
                
            case 'specific_users':
                return $this->audience_filter['user_ids'] ?? [];
                
            default:
                return [];
        }
    }
} 