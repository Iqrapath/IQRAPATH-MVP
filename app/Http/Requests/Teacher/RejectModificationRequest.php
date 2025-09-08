<?php

declare(strict_types=1);

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class RejectModificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'teacher';
    }

    public function rules(): array
    {
        return [
            'teacher_notes' => 'required|string|min:10|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'teacher_notes.required' => 'Please provide a reason for rejection.',
            'teacher_notes.min' => 'The rejection reason must be at least 10 characters.',
            'teacher_notes.max' => 'The rejection reason cannot exceed 1000 characters.',
        ];
    }
}
