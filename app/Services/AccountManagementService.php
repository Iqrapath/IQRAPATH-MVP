<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserAccountAuditLog;
use App\Notifications\AccountSuspendedNotification;
use App\Notifications\AccountUnsuspendedNotification;
use App\Notifications\AccountDeletedNotification;
use App\Notifications\AccountRestoredNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountManagementService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Suspend a user account.
     */
    public function suspendUser(User $user, User $performedBy, string $reason, array $metadata = []): bool
    {
        try {
            return DB::transaction(function () use ($user, $performedBy, $reason, $metadata) {
                // Update user account status
                $user->update([
                    'account_status' => 'suspended',
                    'suspension_reason' => $reason,
                    'suspended_at' => now(),
                    'suspended_by' => $performedBy->id,
                ]);

                // Create audit log
                $this->createAuditLog($user, $performedBy, 'suspended', null, null, null, $reason, $metadata);

                // Send notification to user
                try {
                    $user->notify(new AccountSuspendedNotification($reason));
                } catch (\Exception $e) {
                    Log::warning('Failed to send suspension notification', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }

                // Log the action
                Log::info('User account suspended', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'performed_by' => $performedBy->id,
                    'reason' => $reason
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Failed to suspend user account', [
                'user_id' => $user->id,
                'performed_by' => $performedBy->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Unsuspend a user account.
     */
    public function unsuspendUser(User $user, User $performedBy, string $reason = null, array $metadata = []): bool
    {
        try {
            return DB::transaction(function () use ($user, $performedBy, $reason, $metadata) {
                // Update user account status
                $user->update([
                    'account_status' => 'active',
                    'suspension_reason' => null,
                    'suspended_at' => null,
                    'suspended_by' => null,
                ]);

                // Create audit log
                $this->createAuditLog($user, $performedBy, 'unsuspended', null, null, null, $reason, $metadata);

                // Send notification to user
                try {
                    $user->notify(new AccountUnsuspendedNotification($reason));
                } catch (\Exception $e) {
                    Log::warning('Failed to send unsuspension notification', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }

                // Log the action
                Log::info('User account unsuspended', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'performed_by' => $performedBy->id,
                    'reason' => $reason
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Failed to unsuspend user account', [
                'user_id' => $user->id,
                'performed_by' => $performedBy->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Soft delete a user account.
     */
    public function deleteUser(User $user, User $performedBy, string $reason, array $metadata = []): bool
    {
        try {
            return DB::transaction(function () use ($user, $performedBy, $reason, $metadata) {
                // Create audit log before deletion
                $this->createAuditLog($user, $performedBy, 'deleted', null, null, null, $reason, $metadata);

                // Soft delete the user
                $user->delete();

                // Send notification to user (if possible)
                try {
                    $user->notify(new AccountDeletedNotification($reason));
                } catch (\Exception $e) {
                    Log::warning('Failed to send deletion notification', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }

                // Log the action
                Log::info('User account deleted', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'performed_by' => $performedBy->id,
                    'reason' => $reason
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Failed to delete user account', [
                'user_id' => $user->id,
                'performed_by' => $performedBy->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Restore a soft-deleted user account.
     */
    public function restoreUser(User $user, User $performedBy, string $reason = null, array $metadata = []): bool
    {
        try {
            return DB::transaction(function () use ($user, $performedBy, $reason, $metadata) {
                // Restore the user
                $user->restore();

                // Reset account status to active
                $user->update([
                    'account_status' => 'active',
                    'suspension_reason' => null,
                    'suspended_at' => null,
                    'suspended_by' => null,
                ]);

                // Create audit log
                $this->createAuditLog($user, $performedBy, 'restored', null, null, null, $reason, $metadata);

                // Send notification to user
                try {
                    $user->notify(new AccountRestoredNotification($reason));
                } catch (\Exception $e) {
                    Log::warning('Failed to send restoration notification', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }

                // Log the action
                Log::info('User account restored', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'performed_by' => $performedBy->id,
                    'reason' => $reason
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Failed to restore user account', [
                'user_id' => $user->id,
                'performed_by' => $performedBy->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Permanently delete a user account.
     */
    public function forceDeleteUser(User $user, User $performedBy, string $reason, array $metadata = []): bool
    {
        try {
            return DB::transaction(function () use ($user, $performedBy, $reason, $metadata) {
                // Create audit log before permanent deletion
                $this->createAuditLog($user, $performedBy, 'force_deleted', null, null, null, $reason, $metadata);

                // Permanently delete the user
                $user->forceDelete();

                // Log the action
                Log::warning('User account permanently deleted', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'performed_by' => $performedBy->id,
                    'reason' => $reason
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Failed to permanently delete user account', [
                'user_id' => $user->id,
                'performed_by' => $performedBy->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Change user role and log the action.
     */
    public function changeUserRole(User $user, User $performedBy, string $newRole, string $oldRole, array $metadata = []): bool
    {
        try {
            return DB::transaction(function () use ($user, $performedBy, $newRole, $oldRole, $metadata) {
                // Update user role
                $user->update(['role' => $newRole]);

                // Create audit log
                $this->createAuditLog($user, $performedBy, 'role_changed', 'role', $oldRole, $newRole, $metadata);

                // Log the action
                Log::info('User role changed', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'performed_by' => $performedBy->id,
                    'old_role' => $oldRole,
                    'new_role' => $newRole
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Failed to change user role', [
                'user_id' => $user->id,
                'performed_by' => $performedBy->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Change user account status and log the action.
     */
    public function changeUserStatus(User $user, User $performedBy, string $newStatus, string $oldStatus, string $reason = null, array $metadata = []): bool
    {
        try {
            return DB::transaction(function () use ($user, $performedBy, $newStatus, $oldStatus, $reason, $metadata) {
                // Update user status
                $user->update(['account_status' => $newStatus]);

                // Create audit log
                $this->createAuditLog($user, $performedBy, 'status_changed', 'account_status', $oldStatus, $newStatus, $reason, $metadata);

                // Log the action
                Log::info('User account status changed', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'performed_by' => $performedBy->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'reason' => $reason
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Failed to change user status', [
                'user_id' => $user->id,
                'performed_by' => $performedBy->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create an audit log entry.
     */
    private function createAuditLog(
        User $user,
        User $performedBy,
        string $action,
        ?string $fieldName = null,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?string $reason = null,
        array $metadata = []
    ): void {
        UserAccountAuditLog::create([
            'user_id' => $user->id,
            'performed_by' => $performedBy->id,
            'action' => $action,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $reason,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'performed_at' => now(),
        ]);
    }

    /**
     * Get user audit logs.
     */
    public function getUserAuditLogs(User $user, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return $user->auditLogs()
            ->with('performedBy:id,name,email')
            ->orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get account management statistics.
     */
    public function getAccountManagementStats(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('account_status', 'active')->count();
        $suspendedUsers = User::where('account_status', 'suspended')->count();
        $inactiveUsers = User::where('account_status', 'inactive')->count();
        $pendingUsers = User::where('account_status', 'pending')->count();
        $deletedUsers = User::onlyTrashed()->count();

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'suspended_users' => $suspendedUsers,
            'inactive_users' => $inactiveUsers,
            'pending_users' => $pendingUsers,
            'deleted_users' => $deletedUsers,
            'suspension_rate' => $totalUsers > 0 ? round(($suspendedUsers / $totalUsers) * 100, 2) : 0,
            'deletion_rate' => $totalUsers > 0 ? round(($deletedUsers / $totalUsers) * 100, 2) : 0,
        ];
    }
}
