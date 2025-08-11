<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\GuardianProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GuardianProfile>
 */
class GuardianProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $relationships = ['Parent', 'Grandparent', 'Uncle', 'Aunt', 'Guardian', 'Sibling'];
        $statuses = ['active', 'active', 'active', 'inactive']; // 75% active, 25% inactive

        return [
            'status' => fake()->randomElement($statuses),
            'registration_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'children_count' => 0, // Will be updated later
            'relationship' => fake()->randomElement($relationships),
        ];
    }

    /**
     * Indicate that the guardian profile should be created with a user.
     */
    public function withUser(): static
    {
        return $this->afterCreating(function (GuardianProfile $guardianProfile) {
            if (!$guardianProfile->user_id) {
                $user = User::factory()->guardian()->create();
                $guardianProfile->update(['user_id' => $user->id]);
            }
        });
    }

    /**
     * Indicate that the guardian is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the guardian is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the guardian is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Indicate that the guardian has a specific relationship.
     */
    public function withRelationship(string $relationship): static
    {
        return $this->state(fn (array $attributes) => [
            'relationship' => $relationship,
        ]);
    }

    /**
     * Indicate that the guardian is a parent.
     */
    public function parent(): static
    {
        return $this->state(fn (array $attributes) => [
            'relationship' => 'Parent',
        ]);
    }

    /**
     * Indicate that the guardian is a grandparent.
     */
    public function grandparent(): static
    {
        return $this->state(fn (array $attributes) => [
            'relationship' => 'Grandparent',
        ]);
    }

    /**
     * Indicate that the guardian has a specific number of children.
     */
    public function withChildrenCount(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'children_count' => $count,
        ]);
    }
}
