<?php

declare(strict_types=1);

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentMethodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role === 'teacher' && 
               $this->route('paymentMethod')->user_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $paymentMethod = $this->route('paymentMethod');
        
        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
        ];

        // Type-specific validation (type cannot be changed)
        if ($paymentMethod->type === 'bank_transfer') {
            $rules = array_merge($rules, [
                'bank_code' => ['sometimes', 'string'],
                'bank_name' => ['sometimes', 'string', 'max:255'],
                'account_number' => ['sometimes', 'string', 'digits:10'],
                'account_name' => ['sometimes', 'string', 'max:255'],
            ]);
        }

        if ($paymentMethod->type === 'mobile_money') {
            $rules = array_merge($rules, [
                'provider' => ['sometimes', 'string', Rule::in(['MTN', 'Airtel', 'Vodafone', 'Glo', '9mobile'])],
                'phone_number' => ['sometimes', 'string', 'regex:/^[0-9]{10,15}$/'],
            ]);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.string' => 'Payment method name must be text.',
            'account_number.digits' => 'Account number must be exactly 10 digits.',
            'provider.in' => 'Invalid mobile money provider selected.',
            'phone_number.regex' => 'Please enter a valid phone number.',
        ];
    }
}
