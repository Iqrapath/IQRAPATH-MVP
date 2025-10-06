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
        return in_array($user->role, ['super-admin', 'admin'], true) 
            && $verificationRequest->status === 'pending';
    }

    /**
     * Determine whether the user can reject the verification request.
     */
    public function reject(User $user, VerificationRequest $verificationRequest): bool
    {
        return in_array($user->role, ['super-admin', 'admin'], true) 
            && $verificationRequest->status === 'pending';
    }

    /**
     * Determine whether the user can request a video verification.
     */
    public function requestVideoVerification(User $user, VerificationRequest $verificationRequest): bool
    {
        // Allow super-admin and admin to schedule video verification for any non-finalized request
        return in_array($user->role, ['super-admin', 'admin'], true)
            && !in_array($verificationRequest->status, ['verified', 'rejected'], true);
    }

    /**
     * Determine whether the user can complete a video verification.
     */
    public function completeVideoVerification(User $user, VerificationRequest $verificationRequest): bool
    {
        return in_array($user->role, ['super-admin', 'admin'], true)
            && $verificationRequest->status === 'live_video';
    }

    /**
     * Determine whether the user can verify a document.
     */
    public function verifyDocument(User $user, VerificationRequest $verificationRequest): bool
    {
        return in_array($user->role, ['super-admin', 'admin'], true)
            && !in_array($verificationRequest->status, ['verified', 'rejected'], true);
    }

    /**
     * Determine whether the user can reject a document.
     */
    public function rejectDocument(User $user, VerificationRequest $verificationRequest): bool
    {
        return in_array($user->role, ['super-admin', 'admin'], true)
            && !in_array($verificationRequest->status, ['verified', 'rejected'], true);
    }

    /**
     * Determine whether the user can reopen a rejected verification request.
     */
    public function reopen(User $user, VerificationRequest $verificationRequest): bool
    {
        return in_array($user->role, ['super-admin', 'admin'], true)
            && $verificationRequest->status === 'rejected';
    }
} 