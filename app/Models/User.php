<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'location',
        'status_type',
        'status_message',
        'last_active_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_active_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }

    /**
     * Check if the user is online based on last activity.
     *
     * @return bool
     */
    public function isOnline(): bool
    {
        if (!$this->last_active_at) {
            return false;
        }
        
        return $this->last_active_at->gt(Carbon::now()->subMinutes(5));
    }

    /**
     * Check if the user is away based on last activity.
     *
     * @return bool
     */
    public function isAway(): bool
    {
        if (!$this->last_active_at) {
            return false;
        }
        
        return $this->last_active_at->gt(Carbon::now()->subMinutes(30)) && 
               $this->last_active_at->lt(Carbon::now()->subMinutes(5));
    }

    /**
     * Update the user's status based on their activity.
     *
     * @return void
     */
    public function updateActivityStatus(): void
    {
        // Don't override manually set statuses
        if ($this->status_type !== 'online' && $this->status_type !== 'away' && $this->status_type !== 'offline') {
            return;
        }

        if ($this->isOnline()) {
            $this->status_type = 'online';
        } elseif ($this->isAway()) {
            $this->status_type = 'away';
        } else {
            $this->status_type = 'offline';
        }

        $this->save();
    }
}
