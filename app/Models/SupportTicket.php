<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SupportTicket extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'subject',
        'issue',
        'status',
        'resolved_at',
        'assigned_to',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            // Generate ticket ID if not provided
            if (!$ticket->ticket_id) {
                $latestTicket = static::orderBy('id', 'desc')->first();
                $nextId = $latestTicket ? $latestTicket->id + 1 : 1;
                $ticket->ticket_id = 'TKT-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get the user that submitted the ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the staff assigned to the ticket.
     */
    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the responses for the ticket.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(TicketResponse::class, 'ticket_id');
    }

    /**
     * Get the attachments for the ticket.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(EvidenceAttachment::class, 'attachable');
    }

    /**
     * Get the action logs for the ticket.
     */
    public function actionLogs(): MorphMany
    {
        return $this->morphMany(ActionLog::class, 'loggable');
    }
}
