<?php

namespace App\Policies;

use App\Models\Dispute;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DisputePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Anyone can view disputes list, but what they see will be filtered in the controller
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Dispute $dispute): bool
    {
        // User can view disputes they're involved in, and admins can view any dispute
        return $user->id === $dispute->filed_by || 
               $user->id === $dispute->against || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create a dispute
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Dispute $dispute): bool
    {
        // Only admins can update dispute status
        return $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Dispute $dispute): bool
    {
        // Only admins can delete disputes
        return $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can manage disputes.
     */
    public function manage(User $user): bool
    {
        // Only admins can manage disputes
        return $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can add attachments to the dispute.
     */
    public function addAttachment(User $user, Dispute $dispute): bool
    {
        // User can add attachments to disputes they're involved in, and admins can add to any dispute
        return $user->id === $dispute->filed_by || 
               $user->id === $dispute->against || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }

    /**
     * Determine whether the user can download attachments from the dispute.
     */
    public function downloadAttachment(User $user, Dispute $dispute): bool
    {
        // User can download attachments from disputes they're involved in, and admins can download from any dispute
        return $user->id === $dispute->filed_by || 
               $user->id === $dispute->against || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete attachments from the dispute.
     */
    public function deleteAttachment(User $user, Dispute $dispute): bool
    {
        // Only the user who uploaded the attachment or admins can delete attachments
        // This will be checked in combination with the attachment's uploaded_by field
        return ($user->id === $dispute->filed_by || $user->id === $dispute->against) || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }

    /**
     * Determine whether the user can contact both parties involved in the dispute.
     */
    public function contactParties(User $user, Dispute $dispute): bool
    {
        // Only admins can contact both parties
        return $user->role === 'super-admin' || $user->role === 'admin';
    }
}
