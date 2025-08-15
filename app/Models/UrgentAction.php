<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UrgentAction extends Model
{
    protected $fillable = [
        'type',
        'title',
        'action_text',
        'action_url',
        'count',
        'cached_count',
        'last_updated',
        'priority_level',
        'business_rules',
        'is_active',
        'requires_admin_override',
        'admin_override_at',
        'admin_override_by',
        'permissions',
    ];

    protected $casts = [
        'business_rules' => 'array',
        'permissions' => 'array',
        'last_updated' => 'datetime',
        'admin_override_at' => 'datetime',
        'is_active' => 'boolean',
        'requires_admin_override' => 'boolean',
    ];

    // Priority levels
    const PRIORITY_LOW = 1;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_HIGH = 3;
    const PRIORITY_CRITICAL = 4;

    // Action types
    const TYPE_WITHDRAWAL_REQUESTS = 'withdrawal_requests';
    const TYPE_TEACHER_APPLICATIONS = 'teacher_applications';
    const TYPE_SESSION_ASSIGNMENTS = 'session_assignments';
    const TYPE_DISPUTES = 'disputes';
    const TYPE_PAYMENT_FAILURES = 'payment_failures';
    const TYPE_ACCOUNT_SUSPENSIONS = 'account_suspensions';
    const TYPE_COMPLIANCE_ALERTS = 'compliance_alerts';
    const TYPE_NEW_USER_REGISTRATION = 'new_user_registration';

    public function adminOverrideBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_override_by');
    }

    /**
     * Get urgent actions for a specific user with permission checks
     */
    public static function getForUser(User $user): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "urgent_actions_user_{$user->id}";
        
        return Cache::remember($cacheKey, 120, function () use ($user) {
            return static::where('is_active', true)
                ->where(function ($query) use ($user) {
                    $query->whereNull('permissions')
                        ->orWhereJsonContains('permissions->roles', $user->role)
                        ->orWhereJsonContains('permissions->permissions', '*');
                })
                ->orderBy('priority_level', 'desc')
                ->orderBy('count', 'desc')
                ->get();
        });
    }

    /**
     * Calculate real-time count based on business rules
     */
    public function calculateRealCount(): int
    {
        $rules = $this->business_rules ?? [];
        
        switch ($this->type) {
            case self::TYPE_WITHDRAWAL_REQUESTS:
                return $this->calculateWithdrawalCount($rules);
                
            case self::TYPE_TEACHER_APPLICATIONS:
                return $this->calculateTeacherApplicationCount($rules);
                
            case self::TYPE_SESSION_ASSIGNMENTS:
                return $this->calculateSessionAssignmentCount($rules);
                
            case self::TYPE_DISPUTES:
                return $this->calculateDisputeCount($rules);
                
            case self::TYPE_PAYMENT_FAILURES:
                return $this->calculatePaymentFailureCount($rules);
                
            case self::TYPE_ACCOUNT_SUSPENSIONS:
                return $this->calculateAccountSuspensionCount($rules);
                
            case self::TYPE_COMPLIANCE_ALERTS:
                return $this->calculateComplianceAlertCount($rules);
                
            case self::TYPE_NEW_USER_REGISTRATION:
                return $this->calculateNewUserRegistrationCount($rules);
                
            default:
                return 0;
        }
    }

    /**
     * Update cached count and last_updated
     */
    public function updateCachedCount(): void
    {
        $realCount = $this->calculateRealCount();
        
        $this->update([
            'cached_count' => $realCount,
            'last_updated' => now(),
        ]);
        
        // Clear user-specific caches
        Cache::forget("urgent_actions_user_*");
    }

    /**
     * Check if action meets urgency criteria
     */
    public function isUrgent(): bool
    {
        if ($this->requires_admin_override && $this->admin_override_at) {
            return true;
        }
        
        $rules = $this->business_rules ?? [];
        $count = $this->cached_count;
        
        // Check count threshold
        $minCount = $rules['min_count'] ?? 1;
        if ($count < $minCount) {
            return false;
        }
        
        // Check amount threshold (for financial actions)
        if (isset($rules['min_amount'])) {
            // This would need to be implemented based on your data structure
            // For now, we'll assume it's urgent if count > 0
        }
        
        // Check time threshold
        if (isset($rules['max_days'])) {
            $lastUpdated = $this->last_updated;
            if ($lastUpdated && $lastUpdated->diffInDays(now()) > $rules['max_days']) {
                return false;
            }
        }
        
        return true;
    }

    // Private calculation methods
    private function calculateWithdrawalCount(array $rules): int
    {
        try {
            if (!Schema::hasTable('payout_requests')) {
                \Log::info('payout_requests table does not exist');
                return 0;
            }
            
            $query = DB::table('payout_requests')
                ->where('status', 'pending')
                ->where('created_at', '>=', now()->subDays($rules['max_days'] ?? 7));
                
            if (isset($rules['min_amount'])) {
                $query->where('amount', '>=', $rules['min_amount']);
            }
            
            $count = $query->count();
            \Log::info("Withdrawal count calculated: {$count}");
            return $count;
        } catch (\Exception $e) {
            \Log::error("Error calculating withdrawal count: " . $e->getMessage());
            return 0;
        }
    }

    private function calculateTeacherApplicationCount(array $rules): int
    {
        try {
            if (!Schema::hasTable('verification_requests')) {
                return 0;
            }
            
            return DB::table('verification_requests')
                ->where('status', 'pending')
                ->where('docs_status', 'pending')
                ->where('created_at', '>=', now()->subDays($rules['max_days'] ?? 3))
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function calculateSessionAssignmentCount(array $rules): int
    {
        try {
            if (!Schema::hasTable('teaching_sessions')) {
                return 0;
            }
            
            return DB::table('teaching_sessions')
                ->where('status', 'scheduled')
                ->where('session_date', '>=', now()->toDateString())
                ->where('session_date', '<=', now()->addDays($rules['max_days'] ?? 1)->toDateString())
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function calculateDisputeCount(array $rules): int
    {
        try {
            if (!Schema::hasTable('disputes')) {
                return 0;
            }
            
            return DB::table('disputes')
                ->where('status', 'open')
                ->where('created_at', '>=', now()->subDays($rules['max_days'] ?? 1))
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function calculatePaymentFailureCount(array $rules): int
    {
        try {
            if (!Schema::hasTable('transactions')) {
                return 0;
            }
            
            return DB::table('transactions')
                ->where('status', 'failed')
                ->where('created_at', '>=', now()->subDays($rules['max_days'] ?? 1))
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function calculateAccountSuspensionCount(array $rules): int
    {
        try {
            if (!Schema::hasTable('users')) {
                return 0;
            }
            
            return DB::table('users')
                ->where('account_status', 'suspended')
                ->where('updated_at', '>=', now()->subDays($rules['max_days'] ?? 1))
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function calculateComplianceAlertCount(array $rules): int
    {
        try {
            if (!Schema::hasTable('verification_requests')) {
                return 0;
            }
            
            // Count verification requests that have been pending for too long
            return DB::table('verification_requests')
                ->where('status', 'pending')
                ->where('created_at', '<=', now()->subDays($rules['max_days'] ?? 7))
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function calculateNewUserRegistrationCount(array $rules): int
    {
        try {
            if (!Schema::hasTable('users')) {
                return 0;
            }
            
            return DB::table('users')
                ->whereNull('role')
                ->where('created_at', '>=', now()->subHours($rules['max_hours'] ?? 24))
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
