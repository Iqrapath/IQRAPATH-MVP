<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class EnrollmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'student';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'integer',
                'exists:subscription_plans,id',
                function ($attribute, $value, $fail) {
                    $plan = \App\Models\SubscriptionPlan::find($value);
                    if ($plan && !$plan->is_active) {
                        $fail('The selected plan is no longer available.');
                    }
                },
            ],
            'currency' => 'required|in:USD,NGN',
            'payment_method' => 'required|in:wallet,card,bank_transfer',
            'auto_renew' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'plan_id.required' => 'Please select a subscription plan.',
            'plan_id.exists' => 'The selected plan does not exist.',
            'currency.required' => 'Please select a currency.',
            'currency.in' => 'Please select either USD or NGN.',
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in' => 'Please select a valid payment method.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'plan_id' => 'subscription plan',
            'payment_method' => 'payment method',
            'auto_renew' => 'auto-renewal setting',
        ];
    }
}