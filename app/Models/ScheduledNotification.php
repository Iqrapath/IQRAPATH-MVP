<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheduled_date',
        'message',
        'target_audience',
        'frequency',
        'status',
        'created_by',
        'sent_at',
        'cancelled_at'
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the user who created this notification
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get only scheduled notifications
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope to get only sent notifications
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope to get only cancelled notifications
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope to get notifications due to be sent
     */
    public function scopeDueToSend($query)
    {
        return $query->where('status', 'scheduled')
                    ->where('scheduled_date', '<=', now());
    }

    /**
     * Check if notification is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_date < now();
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);
    }

    /**
     * Mark notification as cancelled
     */
    public function markAsCancelled(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);
    }

    /**
     * Get formatted scheduled date
     */
    public function getFormattedScheduledDateAttribute(): string
    {
        return $this->scheduled_date->format('M j, Y - g:i A');
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'scheduled' => 'text-blue-600',
            'sent' => 'text-green-600',
            'cancelled' => 'text-red-600',
            default => 'text-gray-600'
        };
    }
}
