<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    public function __construct(
        private AccountManagementService $accountManagementService
    ) {}
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by search (name or email)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('email_verified_at', '!=', null)
                      ->where('account_status', 'active');
            } elseif ($request->status === 'inactive') {
                $query->where('email_verified_at', null)
                      ->orWhere('account_status', 'inactive');
            } elseif ($request->status === 'suspended') {
                $query->where('account_status', 'suspended');
            } elseif ($request->status === 'pending') {
                $query->where('account_status', 'pending');
            } elseif ($request->status === 'deleted') {
                $query->onlyTrashed();
            }
        }

        // Filter by role
        if ($request->has('role') && $request->role && $request->role !== 'all') {
            if ($request->role === 'unassigned') {
                $query->whereNull('role');
            } else {
                $query->where('role', $request->role);
            }
        }

        // Get paginated users
        $users = $query->select('id', 'name', 'email', 'avatar', 'role', 'account_status', 'email_verified_at', 'suspended_at', 'suspension_reason')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                    'account_status' => $user->account_status,
                    'account_status_display' => $user->account_status_display,
                    'account_status_color' => $user->account_status_color,
                    'email_verified_at' => $user->email_verified_at,
                    'suspended_at' => $user->suspended_at,
                    'suspension_reason' => $user->suspension_reason,
                    'is_deleted' => $user->trashed(),
                ];
            });

        // Get all available roles for filter dropdown
        $roles = User::whereNotNull('role')
            ->distinct()
            ->pluck('role')
            ->toArray();

        // Always add 'unassigned' role for consistency
        $roles[] = 'unassigned';

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? 'all',
                'role' => $request->role ?? 'all',
            ],
            'roles' => $roles,
            'stats' => $this->accountManagementService->getAccountManagementStats(),
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/users/create');
    }

    public function store(Request $request)
    {
        // TODO: Implement user creation
        return redirect()->route('admin.user-management.index');
    }

    public function show(User $user)
    {
        return Inertia::render('admin/users/show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role' => $user->role,
                'status' => $user->email_verified_at ? 'active' : 'inactive',
                'created_at' => $user->created_at,
                'email_verified_at' => $user->email_verified_at,
            ]
        ]);
    }

    public function edit(User $user)
    {
        return Inertia::render('admin/users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role' => $user->role,
                'status' => $user->email_verified_at ? 'active' : 'inactive',
            ]
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:super-admin,admin,teacher,student,guardian,unassigned'],
        ]);

        // Prevent changing own role
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot change your own role'
            ], 400);
        }

        $oldRole = $user->role;
        $newRole = $validated['role'] === 'unassigned' ? null : $validated['role'];

        try {
            DB::transaction(function() use ($user, $newRole, $oldRole) {
                // Update the user's role
                $user->role = $newRole;
                $user->save();

                // If the role has changed, handle profile creation/deletion
                if ($oldRole !== $newRole) {
                    // Delete old profile if exists
                    if ($oldRole) {
                        $this->deleteOldProfile($user, $oldRole);
                    }

                    // Create new profile only if role is not unassigned
                    if ($newRole) {
                        $this->createNewProfile($user, $newRole);
                    }
                }
            });

            // Only dispatch event if everything succeeded and role is not unassigned
            if ($newRole) {
                event(new \App\Events\UserRoleAssigned($user, $newRole));
            }

            $roleDisplay = $newRole ? ucfirst($newRole) : 'Unassigned';
            
            return response()->json([
                'success' => true,
                'message' => "User role updated to {$roleDisplay}",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                    'status' => $user->email_verified_at ? 'active' : 'inactive',
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role change failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete the old profile based on the previous role.
     */
    private function deleteOldProfile(User $user, string $oldRole): void
    {
        switch ($oldRole) {
            case 'teacher':
                $user->teacherProfile()->delete();
                break;
            case 'student':
                $user->studentProfile()->delete();
                break;
            case 'guardian':
                $user->guardianProfile()->delete();
                break;
            case 'admin':
            case 'super-admin':
                $user->adminProfile()->delete();
                break;
        }
    }

    /**
     * Create new profile based on the new role.
     */
    private function createNewProfile(User $user, string $newRole): void
    {
        switch ($newRole) {
            case 'teacher':
                $user->teacherProfile()->create([
                    'user_id' => $user->id,
                    'bio' => 'New teacher profile',
                    'subjects' => [],
                    'hourly_rate' => 0,
                    'experience_years' => 0,
                ]);
                break;
            case 'student':
                $user->studentProfile()->create([
                    'user_id' => $user->id,
                    'age' => 0,
                    'grade_level' => 'Beginner',
                    'learning_goals' => 'To be defined',
                ]);
                break;
            case 'guardian':
                $user->guardianProfile()->create([
                    'user_id' => $user->id,
                    'relationship' => 'Parent',
                    'emergency_contact' => $user->phone,
                ]);
                break;
            case 'admin':
            case 'super-admin':
                $user->adminProfile()->create([
                    'user_id' => $user->id,
                    'department' => 'Administration',
                    'admin_level' => $newRole === 'super-admin' ? 'Super Admin' : 'Admin',
                    'permissions' => json_encode([
                        'users' => ['create', 'read', 'update', 'delete'],
                        'roles' => ['create', 'read', 'update', 'delete'],
                        'settings' => ['read', 'update'],
                    ]),
                    'bio' => 'System administrator',
                ]);
                break;
        }
    }

    public function destroy(User $user)
    {
        // TODO: Implement user deletion
        return redirect()->route('admin.user-management.index');
    }

    /**
     * Suspend a user account.
     */
    public function suspend(Request $request, User $user)
    {
        Gate::authorize('suspend', $user);

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $success = $this->accountManagementService->suspendUser(
            $user,
            $request->user(),
            $validated['reason'],
            ['ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'User account has been suspended successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to suspend user account. Please try again.'
        ], 500);
    }

    /**
     * Unsuspend a user account.
     */
    public function unsuspend(Request $request, User $user)
    {
        Gate::authorize('unsuspend', $user);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $success = $this->accountManagementService->unsuspendUser(
            $user,
            $request->user(),
            $validated['reason'] ?? 'Account unsuspended by administrator',
            ['ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'User account has been unsuspended successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to unsuspend user account. Please try again.'
        ], 500);
    }

    /**
     * Soft delete a user account.
     */
    public function delete(Request $request, User $user)
    {
        Gate::authorize('delete', $user);

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $success = $this->accountManagementService->deleteUser(
            $user,
            $request->user(),
            $validated['reason'],
            ['ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'User account has been deleted successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete user account. Please try again.'
        ], 500);
    }

    /**
     * Restore a soft-deleted user account.
     */
    public function restore(Request $request, User $user)
    {
        Gate::authorize('restore', $user);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $success = $this->accountManagementService->restoreUser(
            $user,
            $request->user(),
            $validated['reason'] ?? 'Account restored by administrator',
            ['ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'User account has been restored successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to restore user account. Please try again.'
        ], 500);
    }

    /**
     * Permanently delete a user account.
     */
    public function forceDelete(Request $request, User $user)
    {
        Gate::authorize('forceDelete', $user);

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $success = $this->accountManagementService->forceDeleteUser(
            $user,
            $request->user(),
            $validated['reason'],
            ['ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'User account has been permanently deleted.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to permanently delete user account. Please try again.'
        ], 500);
    }

    /**
     * Get user audit logs.
     */
    public function auditLogs(User $user)
    {
        Gate::authorize('view', $user);

        $auditLogs = $this->accountManagementService->getUserAuditLogs($user);

        return response()->json([
            'success' => true,
            'audit_logs' => $auditLogs
        ]);
    }

    /**
     * Bulk account management operations.
     */
    public function bulkAction(Request $request)
    {
        Gate::authorize('bulkManage', User::class);

        $validated = $request->validate([
            'action' => 'required|string|in:suspend,unsuspend,delete,restore',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'reason' => 'nullable|string|max:1000',
        ]);

        $action = $validated['action'];
        $userIds = $validated['user_ids'];
        $reason = $validated['reason'] ?? "Bulk {$action} operation";
        $performedBy = $request->user();

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($userIds as $userId) {
            try {
                $user = User::withTrashed()->findOrFail($userId);
                
                // Check individual permissions
                if (!$performedBy->can($action, $user)) {
                    $errors[] = "Permission denied for user: {$user->name}";
                    $failedCount++;
                    continue;
                }

                $success = false;
                switch ($action) {
                    case 'suspend':
                        $success = $this->accountManagementService->suspendUser($user, $performedBy, $reason);
                        break;
                    case 'unsuspend':
                        $success = $this->accountManagementService->unsuspendUser($user, $performedBy, $reason);
                        break;
                    case 'delete':
                        $success = $this->accountManagementService->deleteUser($user, $performedBy, $reason);
                        break;
                    case 'restore':
                        $success = $this->accountManagementService->restoreUser($user, $performedBy, $reason);
                        break;
                }

                if ($success) {
                    $successCount++;
                } else {
                    $failedCount++;
                    $errors[] = "Failed to {$action} user: {$user->name}";
                }
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Error processing user ID {$userId}: " . $e->getMessage();
            }
        }

        if ($failedCount === 0) {
            return response()->json([
                'success' => true,
                'message' => "Bulk operation completed successfully. {$successCount} users processed."
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => "Bulk operation completed with errors. {$successCount} successful, {$failedCount} failed.",
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'errors' => $errors
            ]);
        }
    }
}
