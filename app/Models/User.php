<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'role',
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

    /**
     * Check if the user is a super admin.
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super-admin';
    }

    /**
     * Check if the user is a teacher.
     *
     * @return bool
     */
    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    /**
     * Check if the user is a student.
     *
     * @return bool
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Check if the user is a guardian.
     *
     * @return bool
     */
    public function isGuardian(): bool
    {
        return $this->role === 'guardian';
    }

    /**
     * Check if the user has no assigned role.
     *
     * @return bool
     */
    public function isUnassigned(): bool
    {
        return $this->role === null;
    }

    /**
     * Get the admin profile associated with the user.
     */
    public function adminProfile(): HasOne
    {
        return $this->hasOne(AdminProfile::class);
    }

    /**
     * Get the teacher profile associated with the user.
     */
    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    /**
     * Get the student profile associated with the user.
     */
    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    /**
     * Get the guardian profile associated with the user.
     */
    public function guardianProfile(): HasOne
    {
        return $this->hasOne(GuardianProfile::class);
    }

    /**
     * Get the profile based on the user's role.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function profile()
    {
        return match($this->role) {
            'super-admin' => $this->adminProfile,
            'teacher' => $this->teacherProfile,
            'student' => $this->studentProfile,
            'guardian' => $this->guardianProfile,
            default => null,
        };
    }

    /**
     * Get the availabilities for the teacher.
     */
    public function availabilities()
    {
        return $this->hasMany(TeacherAvailability::class, 'teacher_id');
    }
}
