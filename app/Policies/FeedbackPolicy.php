<?php

namespace App\Policies;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FeedbackPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Anyone can view feedback list, but what they see will be filtered in the controller
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Feedback $feedback): bool
    {
        // User can view their own feedback or admins can view any feedback
        return $user->id === $feedback->user_id || $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create feedback
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Feedback $feedback): bool
    {
        // Only admins can update feedback status
        return $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Feedback $feedback): bool
    {
        // Only admins can delete feedback
        return $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can manage feedback.
     */
    public function manage(User $user): bool
    {
        // Only admins can manage feedback
        return $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can add attachments to the feedback.
     */
    public function addAttachment(User $user, Feedback $feedback): bool
    {
        // User can add attachments to their own feedback or admins can add to any
        return $user->id === $feedback->user_id || $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can download attachments from the feedback.
     */
    public function downloadAttachment(User $user, Feedback $feedback): bool
    {
        // User can download attachments from their own feedback or admins can download from any
        return $user->id === $feedback->user_id || $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete attachments from the feedback.
     */
    public function deleteAttachment(User $user, Feedback $feedback): bool
    {
        // Only the user who uploaded the attachment or admins can delete attachments
        // This will be checked in combination with the attachment's uploaded_by field
        return $user->id === $feedback->user_id || $user->role === 'super-admin' || $user->role === 'admin';
    }
}
