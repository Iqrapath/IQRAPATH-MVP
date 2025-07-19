<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'template_name',
        'title',
        'body',
        'audience_type',
        'audience_filter',
        'channels',
        'timing_type',
        'timing_value',
        'timing_unit',
        'level',
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
     * Scope a query to only include enabled triggers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope a query to only include triggers for a specific event.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $event
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForEvent($query, $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Check if the trigger applies to a specific user based on audience settings.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function appliesToUser(User $user): bool
    {
        // If audience is all, it applies to everyone
        if ($this->audience_type === 'all') {
            return true;
        }

        // If audience is role-based, check user's role
        if ($this->audience_type === 'role') {
            $roles = $this->audience_filter['roles'] ?? [];
            return in_array($user->role, $roles);
        }

        // If audience is individual, check user's ID
        if ($this->audience_type === 'individual') {
            $userIds = $this->audience_filter['user_ids'] ?? [];
            return in_array($user->id, $userIds);
        }

        return false;
    }
}
