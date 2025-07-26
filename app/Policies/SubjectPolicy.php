<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubjectPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Teachers can view subjects
        return $user->isTeacher() || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Subject $subject): bool
    {
        // Teachers can view their own subjects
        if ($user->isTeacher()) {
            return $user->teacherProfile && $subject->teacher_profile_id === $user->teacherProfile->id;
        }
        
        // Admins can view any subject
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Teachers can create subjects
        if ($user->isTeacher()) {
            return (bool) $user->teacherProfile;
        }
        
        // Admins can create subjects
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Subject $subject): bool
    {
        // Teachers can update their own subjects
        if ($user->isTeacher()) {
            return $user->teacherProfile && $subject->teacher_profile_id === $user->teacherProfile->id;
        }
        
        // Admins can update any subject
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Subject $subject): bool
    {
        // Teachers can delete their own subjects
        if ($user->isTeacher()) {
            return $user->teacherProfile && $subject->teacher_profile_id === $user->teacherProfile->id;
        }
        
        // Admins can delete any subject
        return $user->isSuperAdmin();
    }
}
