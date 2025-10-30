<?php

declare(strict_types=1);

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentMethodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role === 'teacher';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'type' => ['required', Rule::in(['bank_transfer', 'mobile_money', 'card', 'paypal'])],
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ];

        // Type-specific validation
        if ($this->type === 'bank_transfer') {
            $rules = array_merge($rules, [
                'bank_code' => ['required', 'string'],
                'bank_name' => ['required', 'string', 'max:255'],
                'account_number' => ['required', 'string', 'digits:10'],
                'account_name' => ['sometimes', 'string', 'max:255'],
            ]);
        }

        if ($this->type === 'mobile_money') {
            $rules = array_merge($rules, [
                'provider' => ['required', 'string', Rule::in(['MTN', 'Airtel', 'Vodafone', 'Glo', '9mobile'])],
                'phone_number' => ['required', 'string', 'regex:/^[0-9]{10,15}$/'],
            ]);
        }

        if ($this->type === 'paypal') {
            $rules = array_merge($rules, [
                'gateway' => ['sometimes', 'string'],
                'gateway_token' => ['sometimes', 'string'],
                'metadata' => ['required', 'array'],
                'metadata.paypal_email' => ['required', 'email', 'max:255'],
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
            'type.required' => 'Please select a payment method type.',
            'type.in' => 'Invalid payment method type selected.',
            'name.required' => 'Please provide a name for this payment method.',
            'bank_code.required' => 'Please select a bank.',
            'bank_name.required' => 'Bank name is required.',
            'account_number.required' => 'Account number is required.',
            'account_number.digits' => 'Account number must be exactly 10 digits.',
            'provider.required' => 'Please select a mobile money provider.',
            'provider.in' => 'Invalid mobile money provider selected.',
            'phone_number.required' => 'Phone number is required.',
            'phone_number.regex' => 'Please enter a valid phone number.',
            'metadata.paypal_email.required' => 'PayPal email address is required.',
            'metadata.paypal_email.email' => 'Please enter a valid PayPal email address.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'bank_code' => 'bank',
            'account_number' => 'account number',
            'phone_number' => 'phone number',
        ];
    }
}
