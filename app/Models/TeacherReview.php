<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\TeacherReview
 *
 * @property int $id
 * @property int $teacher_id
 * @property int $student_id
 * @property int $session_id
 * @property int $rating
 * @property string|null $review
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class TeacherReview extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'teacher_id',
        'student_id',
        'session_id',
        'rating',
        'review',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the teacher (user) for this review.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the student (user) who wrote this review.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the teaching session this review is for.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TeachingSession::class, 'session_id');
    }

    /**
     * Get a formatted date for display.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('jS F, Y') : '';
    }

    /**
     * Get a short display name for the student (e.g., first name).
     */
    public function getStudentDisplayNameAttribute(): string
    {
        return $this->student ? $this->student->name : 'Student';
    }

    /**
     * Get a short display name for the teacher (e.g., first name).
     */
    public function getTeacherDisplayNameAttribute(): string
    {
        return $this->teacher ? $this->teacher->name : 'Teacher';
    }
}
