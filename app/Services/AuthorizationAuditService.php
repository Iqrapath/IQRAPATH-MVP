<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuthorizationAuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AuthorizationAuditService
{
    /**
     * Log an authorization attempt.
     *
     * @param  User  $user
     * @param  string  $action
     * @param  string  $resource
     * @param  int  $resourceId
     * @param  bool  $granted
     * @param  string|null  $reason
     * @return void
     */
    public function logAuthorizationAttempt(
        User $user,
        string $action,
        string $resource,
        int $resourceId,
        bool $granted,
        ?string $reason = null
    ): void {
        try {
            AuthorizationAuditLog::create([
                'user_id' => $user->id,
                'action' => $action,
                'resource_type' => $resource,
                'resource_id' => $resourceId,
                'granted' => $granted,
                'reason' => $reason,
                'ip_address' => request()->ip() ?? '0.0.0.0',
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'user_role' => $user->role,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            // Log the failure but don't block the operation
            Log::error('Failed to create authorization audit log', [
                'user_id' => $user->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log a role-based messaging rule violation.
     *
     * @param  User  $sender
     * @param  User  $recipient
     * @param  string  $reason
     * @return void
     */
    public function logRoleViolation(
        User $sender,
        User $recipient,
        string $reason
    ): void {
        try {
            AuthorizationAuditLog::create([
                'user_id' => $sender->id,
                'action' => 'create_conversation',
                'resource_type' => 'User',
                'resource_id' => $recipient->id,
                'granted' => false,
                'reason' => $reason,
                'ip_address' => request()->ip() ?? '0.0.0.0',
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'sender_role' => $sender->role,
                    'recipient_role' => $recipient->role,
                    'violation_type' => 'role_restriction',
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log role violation', [
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Flag suspicious activity based on multiple authorization failures.
     *
     * @param  User  $user
     * @param  string  $pattern
     * @return void
     */
    public function flagSuspiciousActivity(
        User $user,
        string $pattern
    ): void {
        try {
            Log::warning('Suspicious activity detected', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'pattern' => $pattern,
                'ip_address' => request()->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Create audit log entry
            AuthorizationAuditLog::create([
                'user_id' => $user->id,
                'action' => 'suspicious_activity',
                'resource_type' => 'System',
                'resource_id' => 0,
                'granted' => false,
                'reason' => $pattern,
                'ip_address' => request()->ip() ?? '0.0.0.0',
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'flagged' => true,
                    'pattern' => $pattern,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to flag suspicious activity', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get audit logs with optional filters.
     *
     * @param  User|null  $user
     * @param  string|null  $action
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     * @return Collection
     */
    public function getAuditLog(
        ?User $user = null,
        ?string $action = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): Collection {
        $query = AuthorizationAuditLog::query()->with('user');

        if ($user) {
            $query->where('user_id', $user->id);
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Check for suspicious patterns and flag if necessary.
     *
     * @param  User  $user
     * @param  int  $threshold
     * @param  int  $minutes
     * @return bool
     */
    public function checkForSuspiciousPattern(
        User $user,
        int $threshold = 5,
        int $minutes = 10
    ): bool {
        $recentFailures = AuthorizationAuditLog::where('user_id', $user->id)
            ->where('granted', false)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();

        if ($recentFailures >= $threshold) {
            $this->flagSuspiciousActivity(
                $user,
                "Multiple authorization failures: {$recentFailures} failures in {$minutes} minutes"
            );
            return true;
        }

        return false;
    }

    /**
     * Log an admin override action.
     *
     * @param  User  $admin
     * @param  string  $action
     * @param  string  $resource
     * @param  int  $resourceId
     * @param  string  $justification
     * @return void
     */
    public function logAdminOverride(
        User $admin,
        string $action,
        string $resource,
        int $resourceId,
        string $justification
    ): void {
        try {
            AuthorizationAuditLog::create([
                'user_id' => $admin->id,
                'action' => $action,
                'resource_type' => $resource,
                'resource_id' => $resourceId,
                'granted' => true,
                'reason' => 'admin_override',
                'ip_address' => request()->ip() ?? '0.0.0.0',
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'admin_role' => $admin->role,
                    'justification' => $justification,
                    'override' => true,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);

            Log::info('Admin override logged', [
                'admin_id' => $admin->id,
                'action' => $action,
                'resource' => $resource,
                'resource_id' => $resourceId,
                'justification' => $justification,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log admin override', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
