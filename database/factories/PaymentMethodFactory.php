<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'bank_transfer',
            'name' => $this->faker->company . ' Account',
            'bank_name' => $this->faker->randomElement([
                'Access Bank',
                'GTBank',
                'First Bank',
                'Zenith Bank',
                'UBA',
                'Fidelity Bank',
            ]),
            'bank_code' => $this->faker->randomElement(['044', '058', '011', '057', '033', '070']),
            'account_name' => $this->faker->name,
            'account_number' => $this->faker->numerify('##########'),
            'last_four' => $this->faker->numerify('####'),
            'currency' => 'NGN',
            'is_default' => false,
            'is_active' => true,
            'is_verified' => true,
            'verification_status' => 'verified',
            'verified_at' => now(),
        ];
    }

    /**
     * Indicate that the payment method is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
            'verification_status' => 'pending',
            'verified_at' => null,
        ]);
    }

    /**
     * Indicate that the payment method is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the payment method is the default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the payment method is a card.
     */
    public function card(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'card',
            'card_brand' => $this->faker->randomElement(['visa', 'mastercard', 'verve']),
            'last_four' => $this->faker->numerify('####'),
            'exp_month' => $this->faker->numberBetween(1, 12),
            'exp_year' => $this->faker->numberBetween(2024, 2030),
            'bank_name' => null,
            'bank_code' => null,
            'account_name' => null,
            'account_number' => null,
        ]);
    }

    /**
     * Indicate that the payment method is mobile money.
     */
    public function mobileMoney(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'mobile_money',
            'provider' => $this->faker->randomElement(['MTN', 'Airtel', 'Glo', '9mobile']),
            'phone_number' => $this->faker->numerify('080########'),
            'bank_name' => null,
            'bank_code' => null,
            'account_name' => null,
            'account_number' => null,
        ]);
    }
}
