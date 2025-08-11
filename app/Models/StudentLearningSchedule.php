<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLearningSchedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
        'time_zone',
        'preference_level',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_active' => 'boolean',
    ];

    /**
     * Get the student that owns the learning schedule.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the day name from the day number.
     */
    public function getDayNameAttribute(): string
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return $days[$this->day_of_week] ?? 'Unknown';
    }

    /**
     * Get the preference level display name.
     */
    public function getPreferenceLevelDisplayAttribute(): string
    {
        return ucfirst($this->preference_level);
    }

    /**
     * Scope to get only active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get schedules for a specific day.
     */
    public function scopeForDay($query, int $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope to get schedules with high preference.
     */
    public function scopeHighPreference($query)
    {
        return $query->where('preference_level', 'high');
    }

    /**
     * Check if the schedule overlaps with another schedule.
     */
    public function overlapsWith(StudentLearningSchedule $other): bool
    {
        if ($this->day_of_week !== $other->day_of_week) {
            return false;
        }

        $thisStart = $this->start_time;
        $thisEnd = $this->end_time;
        $otherStart = $other->start_time;
        $otherEnd = $other->end_time;

        return $thisStart < $otherEnd && $thisEnd > $otherStart;
    }

    /**
     * Get the duration of the schedule in minutes.
     */
    public function getDurationInMinutesAttribute(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Check if the schedule is valid (start time before end time).
     */
    public function isValidTimeRange(): bool
    {
        return $this->start_time < $this->end_time;
    }
}
