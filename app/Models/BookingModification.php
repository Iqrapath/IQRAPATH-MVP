<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BookingModification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'modification_uuid',
        'booking_id',
        'student_id',
        'teacher_id',
        'type',
        'status',
        'original_booking_date',
        'original_start_time',
        'original_end_time',
        'original_duration_minutes',
        'new_booking_date',
        'new_start_time',
        'new_end_time',
        'new_duration_minutes',
        'new_meeting_platform',
        'new_meeting_url',
        'new_teacher_id',
        'new_subject_id',
        'reason',
        'teacher_notes',
        'admin_notes',
        'requested_at',
        'responded_at',
        'expires_at',
        'completed_at',
        'is_urgent',
        'requires_admin_approval',
        'auto_approved',
        'price_difference',
        'payment_status',
        'payment_notes',
        'modification_history',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'original_booking_date' => 'date',
        'original_start_time' => 'datetime:H:i:s',
        'original_end_time' => 'datetime:H:i:s',
        'new_booking_date' => 'date',
        'new_start_time' => 'datetime:H:i:s',
        'new_end_time' => 'datetime:H:i:s',
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_urgent' => 'boolean',
        'requires_admin_approval' => 'boolean',
        'auto_approved' => 'boolean',
        'price_difference' => 'decimal:2',
        'modification_history' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->modification_uuid)) {
                $model->modification_uuid = Str::uuid();
            }
        });
    }

    // Relationships
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function newTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'new_teacher_id');
    }

    public function newSubject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'new_subject_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'related_id')
            ->where('related_type', 'booking_modification');
    }

    // Scopes
    public function scopeReschedule($query)
    {
        return $query->where('type', 'reschedule');
    }

    public function scopeRebook($query)
    {
        return $query->where('type', 'rebook');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUrgent($query)
    {
        return $query->where('is_urgent', true);
    }

    public function scopeExpiringSoon($query, $hours = 24)
    {
        return $query->where('expires_at', '<=', now()->addHours($hours))
            ->where('status', 'pending');
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    // Accessors & Mutators
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast() && $this->status === 'pending';
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    public function getFormattedTypeAttribute(): string
    {
        return match($this->type) {
            'reschedule' => 'Reschedule Request',
            'rebook' => 'Rebook Request',
            default => 'Booking Modification'
        };
    }

    public function getFormattedStatusAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            default => 'Unknown'
        };
    }

    // Business Logic Methods
    public function canBeApproved(): bool
    {
        return $this->status === 'pending' && !$this->is_expired;
    }

    public function canBeRejected(): bool
    {
        return $this->status === 'pending' && !$this->is_expired;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'approved']);
    }

    public function approve(string $teacherNotes = null, int $updatedBy = null): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'teacher_notes' => $teacherNotes,
            'responded_at' => now(),
            'updated_by' => $updatedBy,
        ]);

        $this->addToHistory('approved', $teacherNotes, $updatedBy);

        return true;
    }

    public function reject(string $teacherNotes = null, int $updatedBy = null): bool
    {
        if (!$this->canBeRejected()) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'teacher_notes' => $teacherNotes,
            'responded_at' => now(),
            'updated_by' => $updatedBy,
        ]);

        $this->addToHistory('rejected', $teacherNotes, $updatedBy);

        return true;
    }

    public function cancel(int $updatedBy = null): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'updated_by' => $updatedBy,
        ]);

        $this->addToHistory('cancelled', null, $updatedBy);

        return true;
    }

    public function complete(int $updatedBy = null): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'updated_by' => $updatedBy,
        ]);

        $this->addToHistory('completed', null, $updatedBy);

        return true;
    }

    public function markAsExpired(): bool
    {
        if ($this->status !== 'pending' || !$this->is_expired) {
            return false;
        }

        $this->update([
            'status' => 'expired',
            'updated_by' => null, // System action
        ]);

        $this->addToHistory('expired', 'Automatically expired', null);

        return true;
    }

    private function addToHistory(string $action, ?string $notes, ?int $userId): void
    {
        $history = $this->modification_history ?? [];
        
        $history[] = [
            'action' => $action,
            'notes' => $notes,
            'user_id' => $userId,
            'timestamp' => now()->toISOString(),
        ];

        $this->update(['modification_history' => $history]);
    }

    // Static Factory Methods
    public static function createRescheduleRequest(
        int $bookingId,
        int $studentId,
        int $teacherId,
        array $newDetails,
        string $reason = null,
        int $createdBy = null
    ): self {
        $booking = Booking::findOrFail($bookingId);
        
        return self::create([
            'booking_id' => $bookingId,
            'student_id' => $studentId,
            'teacher_id' => $teacherId,
            'type' => 'reschedule',
            'original_booking_date' => $booking->booking_date,
            'original_start_time' => $booking->start_time,
            'original_end_time' => $booking->end_time,
            'original_duration_minutes' => $booking->duration_minutes,
            'new_booking_date' => $newDetails['new_booking_date'],
            'new_start_time' => $newDetails['new_start_time'],
            'new_end_time' => $newDetails['new_end_time'],
            'new_duration_minutes' => $newDetails['new_duration_minutes'],
            'new_meeting_platform' => $newDetails['meeting_platform'] ?? 'zoom',
            'reason' => $reason,
            'expires_at' => now()->addDays(3), // 3 days to respond
            'created_by' => $createdBy ?? $studentId,
        ]);
    }

    public static function createRebookRequest(
        int $bookingId,
        int $studentId,
        int $teacherId,
        int $newTeacherId,
        int $newSubjectId,
        array $newDetails,
        string $reason = null,
        int $createdBy = null
    ): self {
        $booking = Booking::findOrFail($bookingId);
        
        return self::create([
            'booking_id' => $bookingId,
            'student_id' => $studentId,
            'teacher_id' => $teacherId,
            'type' => 'rebook',
            'original_booking_date' => $booking->booking_date,
            'original_start_time' => $booking->start_time,
            'original_end_time' => $booking->end_time,
            'original_duration_minutes' => $booking->duration_minutes,
            'new_teacher_id' => $newTeacherId,
            'new_subject_id' => $newSubjectId,
            'new_booking_date' => $newDetails['new_booking_date'],
            'new_start_time' => $newDetails['new_start_time'],
            'new_end_time' => $newDetails['new_end_time'],
            'new_duration_minutes' => $newDetails['new_duration_minutes'],
            'new_meeting_platform' => $newDetails['meeting_platform'] ?? 'zoom',
            'reason' => $reason,
            'expires_at' => now()->addDays(5), // 5 days to respond for rebook
            'created_by' => $createdBy ?? $studentId,
        ]);
    }
}