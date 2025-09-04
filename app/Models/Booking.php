<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_uuid',
        'student_id',
        'teacher_id',
        'subject_id',
        'booking_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'status',
        'notes',
        'created_by_id',
        'approved_by_id',
        'approved_at',
        'cancelled_by_id',
        'cancelled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'booking_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'duration_minutes' => 'integer',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            // Generate a unique booking ID if not provided
            if (!$booking->booking_uuid) {
                $booking->booking_uuid = 'BK-' . date('ymd') . str_pad(random_int(1, 999), 3, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get the student associated with the booking.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the teacher associated with the booking.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the subject associated with the booking.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the user who created the booking.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who approved the booking.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    /**
     * Get the user who cancelled the booking.
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    /**
     * Get the notes for the booking.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(BookingNote::class);
    }

    /**
     * Get the notifications for the booking.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(BookingNotification::class);
    }

    /**
     * Get the history for the booking.
     */
    public function history(): HasMany
    {
        return $this->hasMany(BookingHistory::class);
    }

    /**
     * Scope a query to only include bookings with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include upcoming bookings.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming')
            ->where('booking_date', '>=', now()->format('Y-m-d'));
    }

    /**
     * Scope a query to only include bookings for a specific student.
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope a query to only include bookings for a specific teacher.
     */
    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }
    
    /**
     * Get the teaching session associated with this booking.
     */
    public function teachingSession()
    {
        return $this->hasOne(TeachingSession::class);
    }
    
    /**
     * Create a teaching session from this booking.
     */
    public function createSession()
    {
        if ($this->teachingSession()->exists()) {
            return $this->teachingSession;
        }
        
        $session = TeachingSession::create([
            'booking_id' => $this->id,
            'teacher_id' => $this->teacher_id,
            'student_id' => $this->student_id,
            'subject_id' => $this->subject_id,
            'session_date' => $this->booking_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'status' => 'scheduled',
        ]);
        
        // Try to create a Zoom meeting for this session
        try {
            $this->createZoomMeeting($session);
        } catch (\Exception $e) {
            // Log error but don't fail the session creation
            \Illuminate\Support\Facades\Log::error('Failed to create Zoom meeting: ' . $e->getMessage());
        }
        
        return $session;
    }
    
    /**
     * Create a Zoom meeting for the teaching session.
     */
    public function createZoomMeeting(TeachingSession $session)
    {
        $zoomService = app(\App\Services\ZoomService::class);
        return $zoomService->createMeeting($session, $session->teacher);
    }

    /**
     * Create a Google Meet event for the teaching session.
     */
    public function createGoogleMeetEvent(TeachingSession $session)
    {
        $googleMeetService = app(\App\Services\GoogleMeetService::class);
        return $googleMeetService->createMeeting($session, $session->teacher);
    }
} 