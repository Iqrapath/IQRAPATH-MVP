<?php

namespace Database\Factories;

use App\Models\PayoutRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PayoutRequest>
 */
class PayoutRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PayoutRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'amount' => fake()->randomFloat(2, 500, 10000),
            'payment_method' => 'bank_transfer',
            'payment_details' => [
                'bank_name' => fake()->company() . ' Bank',
                'account_name' => fake()->name(),
                'account_number' => fake()->numerify('##########'),
            ],
            'status' => 'pending',
            'request_date' => now()->format('Y-m-d'),
            'currency' => 'NGN',
            'notes' => null,
        ];
    }

    /**
     * Indicate that the payout request is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the payout request is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    /**
     * Indicate that the payout request is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'processed_date' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate that the payout request is for a teacher.
     */
    public function forTeacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->teacher(),
        ]);
    }

    /**
     * Indicate that the payout request is for a student.
     */
    public function forStudent(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->student(),
        ]);
    }
}
