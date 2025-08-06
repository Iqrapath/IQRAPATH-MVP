<?php

namespace App\Policies;

use App\Models\TeachingSession;
use App\Models\User;
use App\Models\GuardianProfile;

class TeacherReviewPolicy
{
    /**
     * Determine if the user can review the given session.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TeachingSession  $session
     * @return bool
     */
    public function create(User $user, TeachingSession $session): bool
    {
        // Only allow if session is completed
        if ($session->status !== 'completed') {
            return false;
        }

        // Allow if user is the student
        if ($user->id === $session->student_id && $user->role === 'student') {
            return true;
        }

        // Allow if user is a guardian of the student
        if ($user->role === 'guardian') {
            // Check if this guardian is linked to the student
            $guardianProfile = GuardianProfile::where('user_id', $user->id)->first();
            if ($guardianProfile && $guardianProfile->id === $session->student->guardian_id) {
                return true;
            }
        }

        return false;
    }
}
