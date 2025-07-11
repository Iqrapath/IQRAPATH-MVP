<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TeachingSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_uuid',
        'booking_id',
        'teacher_id',
        'student_id',
        'subject_id',
        'session_date',
        'start_time',
        'end_time',
        'actual_duration_minutes',
        'status',
        'meeting_link',
        'meeting_platform',
        'meeting_password',
        'zoom_meeting_id',
        'zoom_host_id',
        'zoom_join_url',
        'zoom_start_url',
        'zoom_password',
        'teacher_marked_present',
        'student_marked_present',
        'attendance_data',
        'teacher_joined_at',
        'student_joined_at',
        'teacher_left_at',
        'student_left_at',
        'recording_url',
        'teacher_notes',
        'student_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'session_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'actual_duration_minutes' => 'integer',
        'teacher_marked_present' => 'boolean',
        'student_marked_present' => 'boolean',
        'attendance_data' => 'array',
        'teacher_joined_at' => 'datetime',
        'student_joined_at' => 'datetime',
        'teacher_left_at' => 'datetime',
        'student_left_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            // Generate a unique session ID if not provided
            if (!$session->session_uuid) {
                $session->session_uuid = 'S-' . date('ymd') . str_pad(random_int(1, 999), 3, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get the booking associated with the session.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the teacher associated with the session.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the student associated with the session.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the subject associated with the session.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the materials for the session.
     */
    public function materials(): HasMany
    {
        return $this->hasMany(SessionMaterial::class, 'session_id');
    }

    /**
     * Get the progress record for the session.
     */
    public function progress(): HasOne
    {
        return $this->hasOne(SessionProgress::class, 'session_id');
    }

    /**
     * Scope a query to only include sessions with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include upcoming sessions.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'scheduled')
            ->where('session_date', '>=', now()->format('Y-m-d'));
    }

    /**
     * Scope a query to only include sessions for a specific student.
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope a query to only include sessions for a specific teacher.
     */
    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Check if the session is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if the session is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Calculate the duration of the session in minutes.
     */
    public function calculateDuration(): int
    {
        if ($this->teacher_joined_at && $this->teacher_left_at) {
            $start = $this->teacher_joined_at;
            $end = $this->teacher_left_at;
            
            return $start->diffInMinutes($end);
        }
        
        return 0;
    }
} 