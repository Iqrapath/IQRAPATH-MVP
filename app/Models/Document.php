<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'teacher_profile_id',
        'type',
        'name',
        'path',
        'status',
        'verified_at',
        'verified_by',
        'rejection_reason',
        'metadata',
        'resubmission_count',
        'max_resubmissions',
        'uploaded_by',
        'uploaded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'verified_at' => 'datetime',
        'uploaded_at' => 'datetime',
        'resubmission_count' => 'integer',
        'max_resubmissions' => 'integer',
    ];

    /**
     * Document types
     */
    const TYPE_ID_VERIFICATION = 'id_verification';
    const TYPE_CERTIFICATE = 'certificate';
    const TYPE_RESUME = 'resume';

    /**
     * Document statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';

    /**
     * Maximum resubmission attempts (industry standard)
     */
    const MAX_RESUBMISSION_ATTEMPTS = 3;

    /**
     * Get the teacher profile that owns the document.
     */
    public function teacherProfile(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class);
    }

    /**
     * Get the admin who verified the document.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the admin who uploaded the document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope a query to only include pending documents.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include verified documents.
     */
    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    /**
     * Scope a query to only include rejected documents.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope a query to only include documents of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if document is pending verification.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if document is verified.
     */
    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    /**
     * Check if document is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if document can be resubmitted.
     */
    public function canBeResubmitted(): bool
    {
        return $this->resubmission_count < $this->max_resubmissions;
    }

    /**
     * Check if document has reached maximum resubmission attempts.
     */
    public function hasReachedMaxResubmissions(): bool
    {
        return $this->resubmission_count >= $this->max_resubmissions;
    }

    /**
     * Get remaining resubmission attempts.
     */
    public function getRemainingResubmissions(): int
    {
        return max(0, $this->max_resubmissions - $this->resubmission_count);
    }

    /**
     * Mark document as verified.
     */
    public function markAsVerified(User $admin): bool
    {
        return $this->update([
            'status' => self::STATUS_VERIFIED,
            'verified_at' => now(),
            'verified_by' => $admin->id,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Mark document as rejected.
     */
    public function markAsRejected(User $admin, string $reason): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'verified_at' => now(),
            'verified_by' => $admin->id,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Increment resubmission count when document is resubmitted.
     */
    public function incrementResubmissionCount(): bool
    {
        if ($this->hasReachedMaxResubmissions()) {
            return false;
        }

        return $this->update([
            'resubmission_count' => $this->resubmission_count + 1,
            'status' => self::STATUS_PENDING,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reset resubmission count (when document is verified).
     */
    public function resetResubmissionCount(): bool
    {
        return $this->update([
            'resubmission_count' => 0,
        ]);
    }
}
