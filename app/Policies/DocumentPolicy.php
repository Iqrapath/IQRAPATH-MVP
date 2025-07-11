<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Teachers can view their own documents
        return $user->role === 'teacher';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $document): bool
    {
        // Teachers can only view their own documents
        if ($user->role === 'teacher') {
            return $user->teacherProfile && $document->teacher_profile_id === $user->teacherProfile->id;
        }
        
        // Super-admins can view all documents
        return $user->role === 'super-admin';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only teachers can create documents
        return $user->role === 'teacher' && $user->teacherProfile !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $document): bool
    {
        // Teachers can only update their own pending documents
        if ($user->role === 'teacher') {
            return $user->teacherProfile && 
                   $document->teacher_profile_id === $user->teacherProfile->id &&
                   $document->status === Document::STATUS_PENDING;
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        // Teachers can only delete their own pending documents
        if ($user->role === 'teacher') {
            return $user->teacherProfile && 
                   $document->teacher_profile_id === $user->teacherProfile->id &&
                   $document->status === Document::STATUS_PENDING;
        }
        
        // Super-admins can delete any document
        return $user->role === 'super-admin';
    }

    /**
     * Determine whether the user can verify documents.
     */
    public function verifyDocuments(User $user): bool
    {
        // Only super-admins can verify documents
        return $user->role === 'super-admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Document $document): bool
    {
        // Only super-admins can restore documents
        return $user->role === 'super-admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        // Only super-admins can force delete documents
        return $user->role === 'super-admin';
    }
}
