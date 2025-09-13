<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAvailability extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'teacher_id',
        'holiday_mode',
        'available_days',
        'day_schedules',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
        'time_zone',
        'preferred_teaching_hours',
        'availability_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'holiday_mode' => 'boolean',
        'available_days' => 'array',
        'day_schedules' => 'array',
        'day_of_week' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the teacher that owns the availability.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the day name attribute.
     *
     * @return string
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
     * Format time range for display.
     *
     * @return string
     */
    public function getTimeRangeAttribute(): string
    {
        return date('g:i A', strtotime($this->start_time)) . ' - ' . 
               date('g:i A', strtotime($this->end_time));
    }

    /**
     * Format timezone for display.
     *
     * @return string
     */
    public function getFormattedTimezoneAttribute(): string
    {
        return $this->time_zone ?? 'GMT+0';
    }

    /**
     * Check if teacher is in holiday mode.
     *
     * @return bool
     */
    public function isInHolidayMode(): bool
    {
        return $this->holiday_mode ?? false;
    }

    /**
     * Get available days as array.
     *
     * @return array
     */
    public function getAvailableDaysArray(): array
    {
        return $this->available_days ?? [];
    }

    /**
     * Get day schedules as array.
     *
     * @return array
     */
    public function getDaySchedulesArray(): array
    {
        return $this->day_schedules ?? [];
    }

    /**
     * Check if teacher is available on a specific day.
     *
     * @param string $day
     * @return bool
     */
    public function isAvailableOnDay(string $day): bool
    {
        if ($this->isInHolidayMode()) {
            return false;
        }

        $availableDays = $this->getAvailableDaysArray();
        return in_array($day, $availableDays);
    }

    /**
     * Get schedule for a specific day.
     *
     * @param string $day
     * @return array|null
     */
    public function getScheduleForDay(string $day): ?array
    {
        $schedules = $this->getDaySchedulesArray();
        
        foreach ($schedules as $schedule) {
            if (isset($schedule['day']) && $schedule['day'] === $day) {
                return $schedule;
            }
        }

        return null;
    }
} 