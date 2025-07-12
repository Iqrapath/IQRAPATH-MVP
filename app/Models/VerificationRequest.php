<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VerificationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_profile_id',
        'status',
        'docs_status',
        'video_status',
        'scheduled_call_at',
        'video_platform',
        'meeting_link',
        'notes',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'scheduled_call_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the teacher profile for this verification request.
     */
    public function teacherProfile(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class);
    }

    /**
     * Get all calls scheduled for this verification request.
     */
    public function calls(): HasMany
    {
        return $this->hasMany(VerificationCall::class);
    }

    /**
     * Get all audit logs for this verification request.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(VerificationAuditLog::class);
    }

    /**
     * Get the admin who reviewed this request.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
} 