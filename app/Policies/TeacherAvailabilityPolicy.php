<?php

namespace App\Policies;

use App\Models\TeacherAvailability;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeacherAvailabilityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only teachers can view their availabilities
        return $user->isTeacher();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TeacherAvailability $availability): bool
    {
        // Teachers can only view their own availabilities
        return $user->id === $availability->teacher_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only teachers can create availabilities
        return $user->isTeacher();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TeacherAvailability $availability): bool
    {
        // Teachers can only update their own availabilities
        return $user->id === $availability->teacher_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TeacherAvailability $availability): bool
    {
        // Teachers can only delete their own availabilities
        return $user->id === $availability->teacher_id;
    }
} 