<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeacherProfile>
 */
class TeacherProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $languages = [
            ['English', 'Arabic'],
            ['English', 'Arabic', 'French'],
            ['English', 'Arabic', 'Hausa'],
            ['English', 'Arabic', 'Urdu'],
            ['English', 'Arabic', 'Yoruba'],
            ['English', 'Arabic', 'Spanish'],
        ];

        $teachingTypes = ['Online', 'In-person', 'Hybrid'];
        $teachingModes = ['One-to-One', 'Group', 'Both'];

        $educations = [
            'Al-Azhar University',
            'Islamic University of Madinah',
            'Umm Al-Qura University',
            'Bayero University Kano',
            'International Islamic University Malaysia',
            'Al-Qarawiyyin University',
            'Islamic University of Islamabad',
            'University of Jordan',
        ];

        $qualifications = [
            'Quran Memorization Certificate',
            'Islamic Studies Degree',
            'Quran Sciences Certificate',
            'Islamic Education Diploma',
            'Arabic Language Certificate',
            'Islamic Theology Degree',
            'Tajweed Certification',
            'Islamic Law Degree',
        ];

        // Generate realistic data
        $experienceYears = fake()->numberBetween(1, 20);
        $isVerified = fake()->boolean(70); // 70% chance of being verified
        $rating = $isVerified ? fake()->randomFloat(1, 3.5, 5.0) : fake()->randomFloat(1, 2.0, 4.5);
        $reviewsCount = $isVerified ? fake()->numberBetween(5, 200) : fake()->numberBetween(0, 20);
        
        // Calculate realistic hourly rates based on experience
        $baseRateUSD = 20 + ($experienceYears * 2.5);
        $baseRateNGN = $baseRateUSD * 1200; // Approximate conversion

        return [
            'user_id' => User::factory(), // Will be overridden when passed explicitly
            'bio' => fake()->paragraphs(2, true),
            'experience_years' => (string)$experienceYears,
            'verified' => $isVerified,
            'languages' => fake()->randomElement($languages),
            'teaching_type' => fake()->randomElement($teachingTypes),
            'teaching_mode' => fake()->randomElement($teachingModes),
            'intro_video_url' => null, // Will be uploaded by teacher
            'education' => fake()->randomElement($educations),
            'qualification' => fake()->randomElement($qualifications),
            'rating' => $rating,
            'reviews_count' => $reviewsCount,
            'join_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'hourly_rate_usd' => round($baseRateUSD, 2),
            'hourly_rate_ngn' => round($baseRateNGN, 2),
        ];
    }

    /**
     * Indicate that the teacher profile should be created with a user.
     */
    public function withUser(): static
    {
        return $this->afterCreating(function (TeacherProfile $teacherProfile) {
            if (!$teacherProfile->user_id) {
                $user = User::factory()->teacher()->create();
                $teacherProfile->update(['user_id' => $user->id]);
            }
        });
    }

    /**
     * Indicate that the teacher is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified' => true,
            'rating' => fake()->randomFloat(1, 3.5, 5.0),
            'reviews_count' => fake()->numberBetween(10, 200),
        ]);
    }

    /**
     * Indicate that the teacher is not verified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified' => false,
            'rating' => fake()->randomFloat(1, 2.0, 4.5),
            'reviews_count' => fake()->numberBetween(0, 20),
        ]);
    }

    /**
     * Indicate that the teacher has a specific experience range.
     */
    public function withExperience(int $min, int $max): static
    {
        $experienceYears = fake()->numberBetween($min, $max);
        $baseRateUSD = 20 + ($experienceYears * 2.5);
        $baseRateNGN = $baseRateUSD * 1200;

        return $this->state(fn (array $attributes) => [
            'experience_years' => (string)$experienceYears,
            'hourly_rate_usd' => round($baseRateUSD, 2),
            'hourly_rate_ngn' => round($baseRateNGN, 2),
        ]);
    }

    /**
     * Indicate that the teacher has a specific rate range.
     */
    public function withRateRange(float $min, float $max): static
    {
        $rateUSD = fake()->randomFloat(2, $min, $max);
        $rateNGN = $rateUSD * 1200;

        return $this->state(fn (array $attributes) => [
            'hourly_rate_usd' => $rateUSD,
            'hourly_rate_ngn' => $rateNGN,
        ]);
    }

    /**
     * Indicate that the teacher teaches online.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'teaching_type' => 'Online',
        ]);
    }

    /**
     * Indicate that the teacher teaches in-person.
     */
    public function inPerson(): static
    {
        return $this->state(fn (array $attributes) => [
            'teaching_type' => 'In-person',
        ]);
    }

    /**
     * Indicate that the teacher offers one-to-one sessions.
     */
    public function oneToOne(): static
    {
        return $this->state(fn (array $attributes) => [
            'teaching_mode' => 'One-to-One',
        ]);
    }

    /**
     * Indicate that the teacher offers group sessions.
     */
    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'teaching_mode' => 'Group',
        ]);
    }
}
