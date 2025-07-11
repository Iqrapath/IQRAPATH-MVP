<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SubjectPolicy
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
        
        // Admins with proper permissions can also manage subjects
        if ($user->role === 'super-admin' && $user->adminProfile && 
            isset($user->adminProfile->permissions['subjects']) && 
            in_array($ability, $user->adminProfile->permissions['subjects'])) {
            return true;
        }
        
        return null; // Fall through to the specific policy methods
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Teachers can view subjects
        return $user->isTeacher() && $user->teacherProfile !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Subject $subject): bool
    {
        // User can view a subject if they are the teacher who owns it
        return $user->teacherProfile && $subject->teacher_profile_id === $user->teacherProfile->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only teachers can create subjects
        return $user->isTeacher() && $user->teacherProfile !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Subject $subject): bool
    {
        // User can update a subject if they are the teacher who owns it
        return $user->teacherProfile && $subject->teacher_profile_id === $user->teacherProfile->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Subject $subject): bool
    {
        // User can delete a subject if they are the teacher who owns it
        return $user->teacherProfile && $subject->teacher_profile_id === $user->teacherProfile->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Subject $subject): bool
    {
        // User can restore a subject if they are the teacher who owns it
        return $user->teacherProfile && $subject->teacher_profile_id === $user->teacherProfile->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Subject $subject): bool
    {
        // User can force delete a subject if they are the teacher who owns it
        return $user->teacherProfile && $subject->teacher_profile_id === $user->teacherProfile->id;
    }
}
