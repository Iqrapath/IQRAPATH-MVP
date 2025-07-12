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
}
