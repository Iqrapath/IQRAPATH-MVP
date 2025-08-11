<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'avatar' => null, // Default to null for initials system
            'location' => fake()->city() . ', ' . fake()->country(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('123password'),
            'remember_token' => Str::random(10),
            'role' => fake()->randomElement(['super-admin', 'teacher', 'student', 'guardian']),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
    
    /**
     * Indicate that the user has no avatar.
     */
    public function withoutAvatar(): static
    {
        return $this->state(fn (array $attributes) => [
            'avatar' => null,
        ]);
    }
    
    /**
     * Indicate that the user has no phone number.
     */
    public function withoutPhone(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => null,
        ]);
    }
    
    /**
     * Indicate that the user has no location.
     */
    public function withoutLocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'location' => null,
        ]);
    }

    /**
     * Indicate that the user is a teacher.
     */
    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'teacher',
            'avatar' => null, // Teachers should use initials system
            'location' => fake()->city() . ', Nigeria', // Focus on Nigerian locations
        ]);
    }

    /**
     * Indicate that the user is a student.
     */
    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'student',
            'avatar' => null, // Students can also use initials system
        ]);
    }

    /**
     * Indicate that the user is a guardian.
     */
    public function guardian(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'guardian',
            'avatar' => null, // Guardians can also use initials system
        ]);
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'super-admin',
            'avatar' => null, // Admins can also use initials system
        ]);
    }

    /**
     * Indicate that the user is unassigned (no role).
     */
    public function unassigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => null,
            'avatar' => null, // Unassigned users use initials system
        ]);
    }
}
