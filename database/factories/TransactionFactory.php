<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_uuid' => 'TXN-' . strtoupper($this->faker->unique()->bothify('??##??##??##')),
            'transaction_type' => $this->faker->randomElement(['session_payment', 'referral_bonus', 'system_adjustment']),
            'description' => $this->faker->sentence(),
            'amount' => $this->faker->randomFloat(2, 500, 5000),
            'currency' => 'NGN',
            'status' => 'completed',
            'transaction_date' => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the transaction is for a teacher (session payment).
     */
    public function forTeacher(User $teacher): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_id' => $teacher->id,
            'transaction_type' => 'session_payment',
            'description' => 'Payment for teaching session - ' . $this->faker->randomElement(['Quran Recitation', 'Arabic Language', 'Islamic Studies', 'Tajweed']),
        ]);
    }

    /**
     * Indicate that the transaction is a withdrawal.
     */
    public function withdrawal(User $teacher): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_id' => $teacher->id,
            'transaction_type' => 'withdrawal',
            'description' => 'Withdrawal to bank account',
            'amount' => $this->faker->randomFloat(2, 1000, 10000),
        ]);
    }

    /**
     * Indicate that the transaction is a referral bonus.
     */
    public function referralBonus(User $teacher): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_id' => $teacher->id,
            'transaction_type' => 'referral_bonus',
            'description' => 'Referral bonus for inviting new teacher',
            'amount' => 1000.00,
        ]);
    }

    /**
     * Indicate that the transaction is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the transaction is failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }
}
