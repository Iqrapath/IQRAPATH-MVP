<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class CreateRebookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'student';
    }

    public function rules(): array
    {
        return [
            'booking_id' => 'required|exists:bookings,id',
            'new_teacher_id' => 'required|exists:users,id',
            'new_subject_id' => 'required|exists:subjects,id',
            'new_booking_date' => 'required|date|after:today',
            'new_start_time' => 'required|date_format:H:i',
            'new_end_time' => 'required|date_format:H:i|after:new_start_time',
            'new_duration_minutes' => 'required|integer|min:30|max:480',
            'new_meeting_platform' => 'nullable|string|in:zoom,google_meet,teams,other',
            'reason' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'new_teacher_id.required' => 'Please select a new teacher.',
            'new_subject_id.required' => 'Please select a subject.',
            'new_booking_date.after' => 'The new booking date must be in the future.',
            'new_end_time.after' => 'The end time must be after the start time.',
            'new_duration_minutes.min' => 'The duration must be at least 30 minutes.',
            'new_duration_minutes.max' => 'The duration cannot exceed 8 hours.',
            'reason.max' => 'The reason cannot exceed 1000 characters.',
        ];
    }
}
