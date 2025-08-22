<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class VerificationRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any verification requests.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super-admin', 'admin'], true);
    }

    /**
     * Determine whether the user can view the verification request.
     */
    public function view(User $user, VerificationRequest $verificationRequest): bool
    {
        return in_array($user->role, ['super-admin', 'admin'], true);
    }

    /**
     * Determine whether the user can approve the verification request.
     */
    public function approve(User $user, VerificationRequest $verificationRequest): bool
    {
        return $user->role === 'super-admin' && $verificationRequest->status === 'pending';
    }

    /**
     * Determine whether the user can reject the verification request.
     */
    public function reject(User $user, VerificationRequest $verificationRequest): bool
    {
        return $user->role === 'super-admin' && $verificationRequest->status === 'pending';
    }

    /**
     * Determine whether the user can request a video verification.
     */
    public function requestVideoVerification(User $user, VerificationRequest $verificationRequest): bool
    {
        // Allow super-admin and admin to schedule or generate links while the request is not finalized
        return in_array($user->role, ['super-admin', 'admin'], true)
            && in_array($verificationRequest->status, ['pending', 'live_video'], true);
    }

    /**
     * Determine whether the user can complete a video verification.
     */
    public function completeVideoVerification(User $user, VerificationRequest $verificationRequest): bool
    {
        return in_array($user->role, ['super-admin', 'admin'], true)
            && $verificationRequest->status === 'live_video';
    }
} 