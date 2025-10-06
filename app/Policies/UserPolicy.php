<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Super-admins can do anything
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        return null; // Fall through to the specific policy methods
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'super-admin'], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Users can view their own profile
        if ($user->id === $model->id) {
            return true;
        }
        
        // Admins can view any user
        return in_array($user->role, ['admin', 'super-admin'], true);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'super-admin'], true);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can update their own profile (basic info only)
        if ($user->id === $model->id) {
            return true;
        }
        
        // Admins can update any user
        return in_array($user->role, ['admin', 'super-admin'], true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }
        
        // Cannot delete super-admins (only super-admins can delete super-admins)
        if ($model->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }
        
        // Admins can delete users
        return in_array($user->role, ['admin', 'super-admin'], true);
    }

    /**
     * Determine whether the user can suspend the model.
     */
    public function suspend(User $user, User $model): bool
    {
        // Cannot suspend yourself
        if ($user->id === $model->id) {
            return false;
        }
        
        // Cannot suspend super-admins (only super-admins can suspend super-admins)
        if ($model->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }
        
        // Cannot suspend already suspended users
        if ($model->isAccountSuspended()) {
            return false;
        }
        
        // Admins can suspend users
        return in_array($user->role, ['admin', 'super-admin'], true);
    }

    /**
     * Determine whether the user can unsuspend the model.
     */
    public function unsuspend(User $user, User $model): bool
    {
        // Cannot unsuspend yourself (if you're suspended, you can't access the system)
        if ($user->id === $model->id) {
            return false;
        }
        
        // Cannot unsuspend super-admins (only super-admins can unsuspend super-admins)
        if ($model->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }
        
        // Can only unsuspend suspended users
        if (!$model->isAccountSuspended()) {
            return false;
        }
        
        // Admins can unsuspend users
        return in_array($user->role, ['admin', 'super-admin'], true);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        // Cannot restore yourself
        if ($user->id === $model->id) {
            return false;
        }
        
        // Cannot restore super-admins (only super-admins can restore super-admins)
        if ($model->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }
        
        // Can only restore soft-deleted users
        if (!$model->trashed()) {
            return false;
        }
        
        // Admins can restore users
        return in_array($user->role, ['admin', 'super-admin'], true);
    }

    /**
     * Determine whether the user can force delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Cannot force delete yourself
        if ($user->id === $model->id) {
            return false;
        }
        
        // Only super-admins can force delete
        if (!$user->isSuperAdmin()) {
            return false;
        }
        
        // Cannot force delete super-admins
        if ($model->isSuperAdmin()) {
            return false;
        }
        
        return true;
    }

    /**
     * Determine whether the user can perform bulk operations.
     */
    public function bulkManage(User $user): bool
    {
        return in_array($user->role, ['admin', 'super-admin'], true);
    }

    /**
     * Determine whether the user can change roles.
     */
    public function changeRole(User $user, User $model): bool
    {
        // Cannot change your own role
        if ($user->id === $model->id) {
            return false;
        }
        
        // Cannot change super-admin roles (only super-admins can change super-admin roles)
        if ($model->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }
        
        // Admins can change roles
        return in_array($user->role, ['admin', 'super-admin'], true);
    }

    /**
     * Determine whether the user can view account management actions.
     */
    public function manageAccount(User $user, User $model): bool
    {
        // Cannot manage your own account
        if ($user->id === $model->id) {
            return false;
        }
        
        // Cannot manage super-admin accounts (only super-admins can manage super-admin accounts)
        if ($model->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }
        
        // Admins can manage accounts
        return in_array($user->role, ['admin', 'super-admin'], true);
    }
}
