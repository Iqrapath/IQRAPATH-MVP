<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;
use App\Models\Subscription;
use App\Models\PaymentMethod;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'phone',
        'avatar',
        'location',
        'country',
        'city',
        'role',
        'additional_roles',
        'account_status',
        'suspension_reason',
        'suspended_at',
        'suspended_by',
        'status_type',
        'status_message',
        'last_active_at',
        'registration_date',
        'password',
        'provider',
        'provider_id',
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
            'registration_date' => 'datetime',
            'suspended_at' => 'datetime',
            'password' => 'hashed',
            'additional_roles' => 'array',
        ];
    }

    /**
     * Get the avatar URL attribute
     * Converts storage path to public URL
     */
    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (!$value) {
                    return null;
                }

                // If it's already a full URL, return as is
                if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                    return $value;
                }

                // If it's a storage path (oauth/...), convert to public URL
                if (str_starts_with($value, 'oauth/')) {
                    return asset('storage/' . $value);
                }

                // If it starts with /storage/, convert to full URL
                if (str_starts_with($value, '/storage/')) {
                    return asset($value);
                }

                // Otherwise assume it's in storage and prepend
                return asset('storage/' . $value);
            }
        );
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
     * Check if the user has a specific additional role.
     *
     * @param string $role
     * @return bool
     */
    public function hasAdditionalRole(string $role): bool
    {
        return in_array($role, $this->additional_roles ?? []);
    }

    /**
     * Check if the user has a role (primary or additional).
     *
     * @param string $role
     * @return bool
     */
    public function hasAnyRole(string $role): bool
    {
        return $this->role === $role || $this->hasAdditionalRole($role);
    }

    /**
     * Add an additional role to the user.
     *
     * @param string $role
     * @return bool
     */
    public function addAdditionalRole(string $role): bool
    {
        $additionalRoles = $this->additional_roles ?? [];
        
        if (!in_array($role, $additionalRoles)) {
            $additionalRoles[] = $role;
            $this->additional_roles = $additionalRoles;
            return $this->save();
        }
        
        return true;
    }

    /**
     * Remove an additional role from the user.
     *
     * @param string $role
     * @return bool
     */
    public function removeAdditionalRole(string $role): bool
    {
        $additionalRoles = $this->additional_roles ?? [];
        $additionalRoles = array_filter($additionalRoles, fn($r) => $r !== $role);
        $this->additional_roles = array_values($additionalRoles);
        return $this->save();
    }

    /**
     * Check if the user is a guardian who can also be a student.
     *
     * @return bool
     */
    public function isGuardianStudent(): bool
    {
        return $this->isGuardian() && $this->hasAdditionalRole('student');
    }

    /**
     * Get all roles (primary + additional) for the user.
     *
     * @return array
     */
    public function getAllRoles(): array
    {
        $roles = [$this->role];
        if ($this->additional_roles) {
            $roles = array_merge($roles, $this->additional_roles);
        }
        return array_filter($roles);
    }

    /**
     * Check if the user's account is active.
     */
    public function isAccountActive(): bool
    {
        return $this->account_status === 'active';
    }

    /**
     * Check if the user's account is suspended.
     */
    public function isAccountSuspended(): bool
    {
        return $this->account_status === 'suspended';
    }

    /**
     * Check if the user's account is inactive.
     */
    public function isAccountInactive(): bool
    {
        return $this->account_status === 'inactive';
    }

    /**
     * Check if the user's account is pending.
     */
    public function isAccountPending(): bool
    {
        return $this->account_status === 'pending';
    }

    /**
     * Check if the user's account is deleted (soft deleted).
     */
    public function isAccountDeleted(): bool
    {
        return $this->trashed();
    }

    /**
     * Get the account status display name.
     */
    public function getAccountStatusDisplayAttribute(): string
    {
        return match($this->account_status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            'pending' => 'Pending',
            default => 'Unknown'
        };
    }

    /**
     * Get the account status color for UI.
     */
    public function getAccountStatusColorAttribute(): string
    {
        return match($this->account_status) {
            'active' => 'green',
            'inactive' => 'gray',
            'suspended' => 'red',
            'pending' => 'yellow',
            default => 'gray'
        };
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
     * Get the subjects for the teacher (through teacher profile).
     */
    public function subjects(): HasManyThrough
    {
        return $this->hasManyThrough(Subject::class, TeacherProfile::class, 'user_id', 'teacher_profile_id');
    }

    /**
     * Get the user who suspended this user.
     */
    public function suspendedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'suspended_by');
    }

    /**
     * Get the audit logs for this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(UserAccountAuditLog::class);
    }

    /**
     * Get audit logs performed by this user.
     */
    public function performedAuditLogs(): HasMany
    {
        return $this->hasMany(UserAccountAuditLog::class, 'performed_by');
    }

    /**
     * Get the teacher availabilities associated with the user.
     */
    public function teacherAvailabilities(): HasMany
    {
        return $this->hasMany(TeacherAvailability::class, 'teacher_id');
    }

    /**
     * Get the reviews for this teacher.
     */
    public function teacherReviews(): HasMany
    {
        return $this->hasMany(TeacherReview::class, 'teacher_id');
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
     * Get the student learning schedules associated with the user.
     */
    public function studentLearningSchedules(): HasMany
    {
        return $this->hasMany(StudentLearningSchedule::class, 'student_id');
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
     * Get the payout requests for the user (both teachers and students).
     */
    public function payoutRequests()
    {
        return $this->hasMany(PayoutRequest::class, 'user_id');
    }

    /**
     * Get teacher-specific payout requests.
     */
    public function teacherPayouts()
    {
        return $this->hasMany(PayoutRequest::class, 'user_id')
            ->where(function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('role', 'teacher');
                });
            });
    }

    /**
     * Get student-specific withdrawal requests.
     */
    public function studentWithdrawals()
    {
        return $this->hasMany(PayoutRequest::class, 'user_id')
            ->where(function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('role', 'student');
                });
            });
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
     * Get the user's wallet (for students) - alias for wallet().
     */
    public function studentWallet(): HasOne
    {
        return $this->hasOne(StudentWallet::class);
    }

    /**
     * Get the user's wallet (for teachers).
     */
    public function teacherWallet(): HasOne
    {
        return $this->hasOne(TeacherWallet::class);
    }

    /**
     * Get the user's wallet (for guardians).
     */
    public function guardianWallet(): HasOne
    {
        return $this->hasOne(GuardianWallet::class);
    }

    /**
     * Get the user's virtual accounts.
     */
    public function virtualAccounts(): HasMany
    {
        return $this->hasMany(VirtualAccount::class);
    }

    /**
     * Get the user's active virtual account.
     */
    public function activeVirtualAccount(): HasOne
    {
        return $this->hasOne(VirtualAccount::class)
            ->where('is_active', true)
            ->where('provider', 'paystack')
            ->latestOfMany();
    }

    /**
     * Get the user's payment methods.
     */
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    /**
     * Get the user's active payment methods.
     */
    public function activePaymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class)->active();
    }

    /**
     * Get the user's default payment method.
     */
    public function defaultPaymentMethod(): HasOne
    {
        return $this->hasOne(PaymentMethod::class)->where('is_default', true)->where('is_active', true);
    }

    /**
     * Get available withdrawal balance for students.
     * Calculates wallet balance minus pending/processing withdrawals.
     *
     * @return float
     */
    public function getAvailableWithdrawalBalanceAttribute(): float
    {
        // Only students can withdraw from wallet
        if ($this->role !== 'student') {
            return 0.0;
        }

        // Get student wallet balance
        $walletBalance = $this->studentWallet?->balance ?? 0.0;

        // Get pending withdrawal amounts (pending + processing statuses)
        $pendingWithdrawals = $this->payoutRequests()
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount');

        // Available balance = wallet balance - pending withdrawals
        $availableBalance = $walletBalance - $pendingWithdrawals;

        // Ensure we never return negative balance
        return max(0.0, $availableBalance);
    }

    /**
     * Check if user can withdraw a specific amount.
     * Validates role, minimum amount, and available balance.
     *
     * @param float $amount
     * @return bool
     */
    public function canWithdraw(float $amount): bool
    {
        // Only students can withdraw
        if ($this->role !== 'student') {
            return false;
        }

        // Check minimum withdrawal amount (₦500)
        if ($amount < 500) {
            return false;
        }

        // Check if amount doesn't exceed available balance
        if ($amount > $this->available_withdrawal_balance) {
            return false;
        }

        return true;
    }

    /**
     * Get the user's unified transactions across all wallet types.
     */
    public function unifiedTransactions()
    {
        $walletRelations = [];
        
        if ($this->role === 'student') {
            $walletRelations[] = ['wallet_type' => StudentWallet::class, 'wallet_id' => $this->studentWallet?->id ?? 0];
        }
        
        if ($this->role === 'teacher') {
            $walletRelations[] = ['wallet_type' => TeacherWallet::class, 'wallet_id' => $this->teacherWallet?->id ?? 0];
        }
        
        if ($this->role === 'guardian') {
            $walletRelations[] = ['wallet_type' => GuardianWallet::class, 'wallet_id' => $this->guardianWallet?->id ?? 0];
        }

        $query = UnifiedTransaction::query();
        
        foreach ($walletRelations as $i => $relation) {
            if ($i === 0) {
                $query->where($relation);
            } else {
                $query->orWhere($relation);
            }
        }
        
        return $query;
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
        return $this->hasMany(Notification::class, 'notifiable_id')
            ->where('notifiable_type', self::class);
    }

    /**
     * Get the notifications sent by the user.
     */
    public function sentNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'notifiable_id')
            ->where('notifiable_type', self::class);
    }

    /**
     * Get all unread notifications for the user.
     */
    public function unreadNotifications(): HasMany
    {
        return $this->receivedNotifications()
            ->whereNull('read_at')
            ->where('channel', 'database');
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

    /**
     * Get the OAuth audit logs for this user.
     */
    public function oauthAuditLogs(): HasMany
    {
        return $this->hasMany(\App\Models\OAuthAuditLog::class);
    }

    /**
     * Check if user has OAuth provider linked
     */
    public function hasOAuthProvider(?string $provider = null): bool
    {
        if ($provider === null) {
            return !empty($this->provider);
        }

        return $this->provider === $provider;
    }

    /**
     * Get the linked OAuth provider
     */
    public function getLinkedProvider(): ?string
    {
        return $this->provider;
    }

    /**
     * Check if user can link an OAuth provider
     */
    public function canLinkProvider(string $provider): bool
    {
        // Can link if no provider is currently linked
        if (!$this->provider) {
            return true;
        }

        // Can't link if different provider already linked
        if ($this->provider !== $provider) {
            return false;
        }

        // Already linked to this provider
        return false;
    }

    /**
     * Check if user has password authentication
     */
    public function hasPasswordAuth(): bool
    {
        // Check if password is set and not a random OAuth password
        // OAuth users get a random 32-character password
        return !empty($this->password);
    }

    /**
     * Check if user is OAuth-only (no password)
     */
    public function isOAuthOnly(): bool
    {
        return !empty($this->provider) && !empty($this->provider_id);
    }

    /**
     * Get OAuth provider display name
     */
    public function getOAuthProviderDisplayName(): ?string
    {
        if (!$this->provider) {
            return null;
        }

        return match ($this->provider) {
            'google' => 'Google',
            'facebook' => 'Facebook',
            default => ucfirst($this->provider),
        };
    }

    /**
     * Check if user's avatar is from OAuth
     */
    public function hasOAuthAvatar(): bool
    {
        if (!$this->avatar) {
            return false;
        }

        // Check if avatar is from OAuth storage
        return str_starts_with($this->avatar, 'oauth/');
    }
}
