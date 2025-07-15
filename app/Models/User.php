<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;
use App\Models\Subscription;

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
        
        return $this->last_active_at->gt(Carbon::now()->subMinutes(15)) && 
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
     * Check if the user has a specific role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
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
     * Get the subscriptions for the user.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the user's active subscription.
     *
     * @return Subscription|null
     */
    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()->where('status', 'active')->latest()->first();
    }

    /**
     * Check if the user has an active subscription.
     *
     * @return bool
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()->where('status', 'active')->exists();
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
     * Get the feedback submitted by the user.
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * Get the support tickets submitted by the user.
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * Get the support tickets assigned to the user.
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to');
    }

    /**
     * Get the disputes filed by the user.
     */
    public function filedDisputes(): HasMany
    {
        return $this->hasMany(Dispute::class, 'filed_by');
    }

    /**
     * Get the disputes filed against the user.
     */
    public function disputesAgainst(): HasMany
    {
        return $this->hasMany(Dispute::class, 'against');
    }

    /**
     * Get the ticket responses created by the user.
     */
    public function ticketResponses(): HasMany
    {
        return $this->hasMany(TicketResponse::class, 'responder_id');
    }

    /**
     * Get the evidence attachments uploaded by the user.
     */
    public function uploadedAttachments(): HasMany
    {
        return $this->hasMany(EvidenceAttachment::class, 'uploaded_by');
    }

    /**
     * Get the action logs performed by the user.
     */
    public function actionLogs(): HasMany
    {
        return $this->hasMany(ActionLog::class, 'performed_by');
    }

    /**
     * Get the availabilities for the teacher.
     */
    public function availabilities()
    {
        return $this->hasMany(TeacherAvailability::class, 'teacher_id');
    }

    /**
     * Get the bookings where the user is a student.
     */
    public function studentBookings()
    {
        return $this->hasMany(Booking::class, 'student_id');
    }

    /**
     * Get the bookings where the user is a teacher.
     */
    public function teacherBookings()
    {
        return $this->hasMany(Booking::class, 'teacher_id');
    }

    /**
     * Get all booking notifications for the user.
     */
    public function bookingNotifications()
    {
        return $this->hasMany(BookingNotification::class);
    }

    /**
     * Get the teaching sessions where the user is a teacher.
     */
    public function teachingSessions()
    {
        return $this->hasMany(TeachingSession::class, 'teacher_id');
    }

    /**
     * Get the teaching sessions where the user is a student.
     */
    public function learningSession()
    {
        return $this->hasMany(TeachingSession::class, 'student_id');
    }

    /**
     * Get the earnings record for the teacher.
     */
    public function earnings()
    {
        return $this->hasOne(TeacherEarning::class, 'teacher_id');
    }
    
    /**
     * Get the transactions for the teacher.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'teacher_id');
    }
    
    /**
     * Get the payout requests for the teacher.
     */
    public function payoutRequests()
    {
        return $this->hasMany(PayoutRequest::class, 'teacher_id');
    }

    /**
     * Get the wallet associated with the user.
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(StudentWallet::class);
    }

    /**
     * Get or create a wallet for the user.
     * 
     * @return StudentWallet
     */
    public function getOrCreateWallet()
    {
        if ($this->wallet) {
            return $this->wallet;
        }
        
        return $this->wallet()->create([
            'balance' => 0,
            'payment_methods' => [],
            'default_payment_method' => null,
        ]);
    }

    /**
     * Get the learning progress records for the user.
     */
    public function learningProgress()
    {
        return $this->hasMany(StudentLearningProgress::class);
    }
    
    /**
     * Get messages sent by the user.
     */
    public function sentMessages()
    {
        return $this->hasMany(GuardianMessage::class, 'sender_id');
    }
    
    /**
     * Get messages received by the user.
     */
    public function receivedMessages()
    {
        return $this->hasMany(GuardianMessage::class, 'recipient_id');
    }
    
    /**
     * Get all messages (sent and received) for the user.
     */
    public function allMessages()
    {
        return GuardianMessage::forUser($this->id);
    }
    
    /**
     * Get unread messages for the user.
     */
    public function unreadMessages()
    {
        return $this->receivedMessages()->where('is_read', false);
    }
    
    /**
     * Get the notifications received by the user.
     */
    public function receivedNotifications(): HasMany
    {
        return $this->hasMany(NotificationRecipient::class, 'user_id');
    }

    /**
     * Get the notifications sent by the user.
     */
    public function sentNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'sender_id')
            ->where('sender_type', '!=', 'system');
    }

    /**
     * Get all unread notifications for the user.
     */
    public function unreadNotifications(): HasMany
    {
        return $this->receivedNotifications()
            ->whereNull('read_at')
            ->whereIn('status', ['sent', 'delivered']);
    }

    /**
     * Get all notifications for a specific channel.
     */
    public function notificationsForChannel(string $channel): HasMany
    {
        return $this->receivedNotifications()
            ->where('channel', $channel);
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllNotificationsAsRead(): void
    {
        $this->unreadNotifications()
            ->update([
                'status' => 'read',
                'read_at' => now(),
            ]);
    }
}
