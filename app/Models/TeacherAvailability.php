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
} 