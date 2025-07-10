<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
                function ($attribute, $value, $fail) {
                    // If both email and phone are empty, require at least one
                    if (empty($value) && empty($this->phone)) {
                        $fail('Either email or phone number is required.');
                    }
                },
            ],
            'phone' => [
                'nullable', 
                'string', 
                'max:20',
                function ($attribute, $value, $fail) {
                    // If both email and phone are empty, require at least one
                    if (empty($value) && empty($this->email)) {
                        $fail('Either email or phone number is required.');
                    }
                },
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'status_type' => ['nullable', 'string', 'in:online,away,busy,offline'],
            'status_message' => ['nullable', 'string', 'max:255'],
        ];
    }
}
