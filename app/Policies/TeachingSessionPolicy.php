<?php

namespace App\Policies;

use App\Models\TeachingSession;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeachingSessionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can join as a teacher.
     */
    public function joinAsTeacher(User $user, TeachingSession $session): bool
    {
        // Only the assigned teacher can join as teacher
        return $user->id === $session->teacher_id;
    }

    /**
     * Determine if the user can join as a student.
     */
    public function joinAsStudent(User $user, TeachingSession $session): bool
    {
        // Only the assigned student can join as student
        return $user->id === $session->student_id;
    }

    /**
     * Determine if the user can manage the session.
     */
    public function manageSession(User $user, TeachingSession $session): bool
    {
        // Teachers can manage their own sessions
        if ($user->id === $session->teacher_id) {
            return true;
        }

        // Admins and super-admins can manage any session
        if ($user->role === 'super-admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can view the session.
     */
    public function view(User $user, TeachingSession $session): bool
    {
        // Teachers can view their own sessions
        if ($user->id === $session->teacher_id) {
            return true;
        }

        // Students can view their own sessions
        if ($user->id === $session->student_id) {
            return true;
        }

        // Guardians can view their children's sessions
        if ($user->isGuardian()) {
            // Check if the student is one of the guardian's children
            // This assumes you have a relationship set up between guardians and students
            $guardianStudentIds = $user->guardianProfile->students()->pluck('id')->toArray();
            if (in_array($session->student_id, $guardianStudentIds)) {
                return true;
            }
        }

        // Admins and super-admins can view any session
        if ($user->role === 'super-admin') {
            return true;
        }

        return false;
    }
} 