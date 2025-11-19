<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'subject',
        'context_type',
        'context_id',
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
     * Get all messages in this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get all participants in this conversation.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['is_muted', 'is_archived', 'last_read_at'])
            ->withTimestamps();
    }

    /**
     * Get the latest message in this conversation.
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Scope a query to only include conversations for a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->whereHas('participants', fn($q) => $q->where('user_id', $userId));
    }

    /**
     * Scope a query to only include non-archived conversations for a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotArchived($query, int $userId)
    {
        return $query->whereHas('participants', function($q) use ($userId) {
            $q->where('user_id', $userId)->where('is_archived', false);
        });
    }

    /**
     * Get the unread message count for a specific user.
     *
     * @param  int  $userId
     * @return int
     */
    public function getUnreadCountForUser(int $userId): int
    {
        $participant = $this->participants()->where('user_id', $userId)->first();
        $lastReadAt = $participant?->pivot->last_read_at;

        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->when($lastReadAt, fn($q) => $q->where('created_at', '>', $lastReadAt))
            ->count();
    }
}
