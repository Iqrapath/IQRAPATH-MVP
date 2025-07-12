<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TicketResponse extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_id',
        'responder_id',
        'message',
        'notification_sent',
        'notification_channels',
        'scheduled_for',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'notification_sent' => 'boolean',
        'notification_channels' => 'array',
        'scheduled_for' => 'datetime',
    ];

    /**
     * Get the ticket that the response belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /**
     * Get the user that created the response.
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_id');
    }

    /**
     * Get the attachments for the response.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(EvidenceAttachment::class, 'attachable');
    }
}
