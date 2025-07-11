<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionProgress extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'topic_covered',
        'proficiency_level',
        'teacher_assessment',
        'next_steps',
    ];

    /**
     * Get the session associated with the progress.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TeachingSession::class, 'session_id');
    }

    /**
     * Get the proficiency level display name.
     */
    public function getProficiencyDisplayAttribute(): string
    {
        return match($this->proficiency_level) {
            'beginner' => 'Beginner',
            'intermediate' => 'Intermediate',
            'advanced' => 'Advanced',
            default => 'Unknown',
        };
    }
} 