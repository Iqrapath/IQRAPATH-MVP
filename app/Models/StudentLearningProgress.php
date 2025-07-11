<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLearningProgress extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'student_learning_progress';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'subject_id',
        'progress_percentage',
        'completed_sessions',
        'total_sessions',
        'milestones_completed',
        'certificates_earned',
        'last_updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'progress_percentage' => 'integer',
        'completed_sessions' => 'integer',
        'total_sessions' => 'integer',
        'milestones_completed' => 'array',
        'certificates_earned' => 'array',
        'last_updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the learning progress.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subject associated with the learning progress.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the student profile associated with the learning progress.
     */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'user_id', 'user_id');
    }

    /**
     * Update the progress percentage based on completed sessions.
     *
     * @return bool
     */
    public function updateProgressPercentage(): bool
    {
        if ($this->total_sessions > 0) {
            $this->progress_percentage = (int) (($this->completed_sessions / $this->total_sessions) * 100);
        } else {
            $this->progress_percentage = 0;
        }

        $this->last_updated_at = now();
        return $this->save();
    }

    /**
     * Increment the completed sessions count.
     *
     * @param int $count
     * @return bool
     */
    public function incrementCompletedSessions(int $count = 1): bool
    {
        $this->completed_sessions += $count;
        return $this->updateProgressPercentage();
    }

    /**
     * Add a milestone to the completed milestones.
     *
     * @param array $milestone
     * @return bool
     */
    public function addMilestone(array $milestone): bool
    {
        $milestones = $this->milestones_completed ?? [];
        $milestones[] = array_merge($milestone, ['completed_at' => now()->toDateTimeString()]);
        
        $this->milestones_completed = $milestones;
        $this->last_updated_at = now();
        
        return $this->save();
    }

    /**
     * Add a certificate to the earned certificates.
     *
     * @param array $certificate
     * @return bool
     */
    public function addCertificate(array $certificate): bool
    {
        $certificates = $this->certificates_earned ?? [];
        $certificates[] = array_merge($certificate, ['issued_at' => now()->toDateTimeString()]);
        
        $this->certificates_earned = $certificates;
        $this->last_updated_at = now();
        
        return $this->save();
    }
} 