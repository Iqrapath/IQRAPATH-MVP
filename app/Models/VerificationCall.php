<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'verification_request_id',
        'scheduled_at',
        'platform',
        'meeting_link',
        'notes',
        'status',
        'verification_result',
        'verification_notes',
        'verified_by',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    // Verification result constants
    const RESULT_PASSED = 'passed';
    const RESULT_FAILED = 'failed';

    /**
     * Get the verification request for this call.
     */
    public function verificationRequest(): BelongsTo
    {
        return $this->belongsTo(VerificationRequest::class);
    }

    /**
     * Get the user who created this call.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the admin who verified this call.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Mark video verification as passed.
     */
    public function markAsPassed(User $admin, string $notes = null): void
    {
        $this->update([
            'status' => 'completed',
            'verification_result' => self::RESULT_PASSED,
            'verification_notes' => $notes,
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        // Update verification request video_status
        $this->verificationRequest->update(['video_status' => 'passed']);
    }

    /**
     * Mark video verification as failed.
     */
    public function markAsFailed(User $admin, string $notes): void
    {
        $this->update([
            'status' => 'completed',
            'verification_result' => self::RESULT_FAILED,
            'verification_notes' => $notes,
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        // Update verification request video_status
        $this->verificationRequest->update(['video_status' => 'failed']);
    }

    /**
     * Check if verification result is available.
     */
    public function hasVerificationResult(): bool
    {
        return in_array($this->verification_result, [self::RESULT_PASSED, self::RESULT_FAILED]);
    }

    /**
     * Check if verification passed.
     */
    public function isPassed(): bool
    {
        return $this->verification_result === self::RESULT_PASSED;
    }

    /**
     * Check if verification failed.
     */
    public function isFailed(): bool
    {
        return $this->verification_result === self::RESULT_FAILED;
    }
} 