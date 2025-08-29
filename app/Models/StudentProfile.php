<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date_of_birth',
        'gender',
        'grade_level',
        'school_name',
        'guardian_id',
        'learning_goals',
        'subjects_of_interest',

        'age_group',
        'payment_id',
        'status',
        'registration_date',
        'teaching_mode',
        'additional_notes',
        'timezone',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'subjects_of_interest' => 'array',

        'registration_date' => 'datetime',
    ];

    /**
     * Get the user that owns the student profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the guardian associated with the student.
     */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_id');
    }

    /**
     * Get the learning progress records for the student.
     */
    public function learningProgress(): HasMany
    {
        return $this->hasMany(StudentLearningProgress::class, 'user_id', 'user_id');
    }

    /**
     * Get the learning schedules for the student.
     */
    public function learningSchedules(): HasMany
    {
        return $this->hasMany(StudentLearningSchedule::class, 'student_id', 'user_id');
    }
    
    /**
     * Get the teaching sessions for this student.
     */
    public function teachingSessions(): HasMany
    {
        return $this->hasMany(TeachingSession::class, 'student_id', 'user_id');
    }

    /**
     * Get the bookings for this student.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'student_id', 'user_id');
    }

    /**
     * Get the subscription for this student.
     */
    public function subscription(): HasMany
    {
        return $this->hasMany(Subscription::class, 'user_id', 'user_id');
    }

    /**
     * Get the active subscription for this student.
     */
    public function activeSubscription()
    {
        return $this->subscription()->where('status', 'active')->first();
    }

    /**
     * Get completed sessions count.
     */
    public function getCompletedSessionsCountAttribute(): int
    {
        return $this->teachingSessions()->where('status', 'completed')->count();
    }

    /**
     * Get total sessions count.
     */
    public function getTotalSessionsCountAttribute(): int
    {
        return $this->teachingSessions()->count();
    }

    /**
     * Get attendance percentage.
     */
    public function getAttendancePercentageAttribute(): float
    {
        $totalSessions = $this->total_sessions_count;
        if ($totalSessions === 0) {
            return 0;
        }

        $attendedSessions = $this->teachingSessions()
            ->where('student_marked_present', true)
            ->count();

        return round(($attendedSessions / $totalSessions) * 100, 1);
    }

    /**
     * Get missed sessions count.
     */
    public function getMissedSessionsCountAttribute(): int
    {
        return $this->teachingSessions()
            ->whereIn('status', ['no_show', 'missed'])
            ->count();
    }

    /**
     * Get average engagement score.
     */
    public function getAverageEngagementAttribute(): float
    {
        $averageRating = $this->teachingSessions()
            ->whereNotNull('student_rating')
            ->avg('student_rating');

        return $averageRating ? round($averageRating, 1) : 0;
    }

    /**
     * Check if student is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if student is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Update the guardian's children count when this student is associated.
     */
    public function updateGuardianChildrenCount(): void
    {
        if ($this->guardian_id) {
            $guardianProfile = GuardianProfile::where('user_id', $this->guardian_id)->first();
            if ($guardianProfile) {
                $guardianProfile->updateChildrenCount();
            }
        }
    }

    /**
     * Check if the student is in full-time mode.
     */
    public function isFullTime(): bool
    {
        return $this->teaching_mode === 'full-time';
    }

    /**
     * Check if the student is in part-time mode.
     */
    public function isPartTime(): bool
    {
        return $this->teaching_mode === 'part-time';
    }

    /**
     * Get the teaching mode display name.
     */
    public function getTeachingModeDisplayAttribute(): string
    {
        return ucfirst(str_replace('-', ' ', $this->teaching_mode ?? ''));
    }

    /**
     * Get the age in years.
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }

        return $this->date_of_birth->age;
    }

    /**
     * Get the formatted registration date.
     */
    public function getFormattedRegistrationDateAttribute(): string
    {
        return $this->registration_date ? $this->registration_date->format('M j, Y') : 'N/A';
    }
}
