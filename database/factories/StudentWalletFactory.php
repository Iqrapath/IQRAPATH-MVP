<?php

namespace Database\Factories;

use App\Models\StudentWallet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentWallet>
 */
class StudentWalletFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StudentWallet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'balance' => 0.00,
            'total_spent' => 0.00,
            'total_refunded' => 0.00,
            'default_payment_method_id' => null,
            'auto_renew_enabled' => false,
        ];
    }

    /**
     * Indicate that the wallet has a specific balance.
     */
    public function withBalance(float $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $balance,
        ]);
    }
}
