<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $student = User::factory()->create(['role' => 'student']);
        
        return [
            'student_id' => $student->id,
            'teacher_id' => User::factory()->create(['role' => 'teacher'])->id,
            'subject_id' => Subject::factory(),
            'booking_date' => now()->addDays(rand(1, 30)),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'duration_minutes' => 60,
            'status' => 'pending',
            'notes' => fake()->sentence(),
            'created_by_id' => $student->id, // Student creates the booking
        ];
    }

    /**
     * Indicate that the booking is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Indicate that the booking is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'booking_date' => now()->subDays(rand(1, 30)),
        ]);
    }
}
