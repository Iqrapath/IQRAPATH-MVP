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
        'preferred_learning_times',
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
        'preferred_learning_times' => 'array',
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
        return $this->registration_date ? $this->registration_date->format('F j, Y') : 'N/A';
    }
}
