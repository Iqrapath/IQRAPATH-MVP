<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'type',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the conversation this message belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user who sent this message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get all attachments for this message.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    /**
     * Get all status records for this message.
     */
    public function statuses(): HasMany
    {
        return $this->hasMany(MessageStatus::class);
    }

    /**
     * Scope a query to only include messages in a specific conversation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $conversationId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInConversation($query, int $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    /**
     * Check if this message has been read by a specific user.
     *
     * @param  int  $userId
     * @return bool
     */
    public function isReadBy(int $userId): bool
    {
        return $this->statuses()
            ->where('user_id', $userId)
            ->where('status', 'read')
            ->exists();
    }
}
