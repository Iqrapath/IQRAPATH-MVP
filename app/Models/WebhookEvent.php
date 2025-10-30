<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WebhookEvent extends Model
{
    protected $fillable = [
        'event_id',
        'gateway',
        'type',
        'payload',
        'status',
        'idempotency_key',
        'error_message',
        'retry_count',
        'processed_at',
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'retry_count' => 'integer',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (!$event->idempotency_key) {
                $event->idempotency_key = Str::uuid();
            }
        });
    }

    /**
     * Check if event has already been processed.
     */
    public static function isProcessed(string $eventId, string $gateway): bool
    {
        return self::where('event_id', $eventId)
            ->where('gateway', $gateway)
            ->where('status', 'processed')
            ->exists();
    }

    /**
     * Mark event as processing.
     */
    public function markAsProcessing(): bool
    {
        return $this->update(['status' => 'processing']);
    }

    /**
     * Mark event as processed.
     */
    public function markAsProcessed(): bool
    {
        return $this->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark event as failed.
     */
    public function markAsFailed(string $errorMessage): bool
    {
        return $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'failed_at' => now(),
        ]);
    }

    /**
     * Increment retry count.
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }

    /**
     * Check if max retries reached.
     */
    public function maxRetriesReached(int $maxRetries = 3): bool
    {
        return $this->retry_count >= $maxRetries;
    }

    /**
     * Scope for pending events.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for failed events.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for processed events.
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Scope for specific gateway.
     */
    public function scopeGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Scope for specific event type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
