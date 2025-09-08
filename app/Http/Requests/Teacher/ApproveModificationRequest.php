<?php

declare(strict_types=1);

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class ApproveModificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'teacher';
    }

    public function rules(): array
    {
        return [
            'teacher_notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'teacher_notes.max' => 'The notes cannot exceed 1000 characters.',
        ];
    }
}
