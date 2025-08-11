<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gradeLevels = ['Beginner', 'Intermediate', 'Advanced', 'Expert'];
        $statuses = ['active', 'active', 'active', 'inactive']; // 75% active, 25% inactive
        $genders = ['male', 'female'];
        $ageGroups = ['5-8', '9-12', '13-15', '16-18', '19+'];
        
        $schoolNames = [
            'Islamic Academy Lagos', 'Madrasah Al-Noor', 'Islamic Center Abuja',
            'Quran Institute Kano', 'Islamic School Port Harcourt', 'Madrasah Al-Huda',
            'Islamic Learning Center', 'Quran Academy', 'Islamic Education Institute',
            'Al-Hikmah Islamic School', 'Madrasah Al-Rahman', 'Islamic Foundation School'
        ];
        
        $learningGoals = [
            'Learn proper Quran recitation with Tajweed',
            'Memorize selected surahs and duas',
            'Complete Quran memorization (Hifz)',
            'Learn Islamic studies and history',
            'Improve Arabic language skills',
            'Learn Islamic etiquette and manners',
            'Master advanced Tajweed rules',
            'Study Islamic jurisprudence (Fiqh)',
            'Learn about Prophet Muhammad (PBUH) life',
            'Understand Islamic values and ethics'
        ];
        
        $subjectsOfInterest = [
            'Tajweed', 'Hifz', 'Islamic Studies', 'Tawheed', 'Qaida',
            'Islamic History', 'Hadith Studies', 'Arabic Language', 'Islamic Etiquette',
            'Fiqh', 'Seerah', 'Aqeedah', 'Islamic Art and Calligraphy'
        ];
        
        $preferredLearningTimes = ['Morning', 'Afternoon', 'Evening', 'Weekend'];

        return [
            'date_of_birth' => fake()->dateTimeBetween('-18 years', '-5 years'),
            'gender' => fake()->randomElement($genders),
            'status' => fake()->randomElement($statuses),
            'registration_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'grade_level' => fake()->randomElement($gradeLevels),
            'school_name' => fake()->randomElement($schoolNames),
            'guardian_id' => null, // Will be assigned later
            'learning_goals' => fake()->randomElement($learningGoals),
            'subjects_of_interest' => json_encode(fake()->randomElements($subjectsOfInterest, fake()->numberBetween(2, 4))),
            'preferred_learning_times' => json_encode(fake()->randomElements($preferredLearningTimes, fake()->numberBetween(1, 3))),
            'age_group' => fake()->randomElement($ageGroups),
            'payment_id' => null,
        ];
    }

    /**
     * Indicate that the student profile should be created with a user.
     */
    public function withUser(): static
    {
        return $this->afterCreating(function (StudentProfile $studentProfile) {
            if (!$studentProfile->user_id) {
                $user = User::factory()->student()->create();
                $studentProfile->update(['user_id' => $user->id]);
            }
        });
    }

    /**
     * Indicate that the student is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the student is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the student is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Indicate that the student has a specific grade level.
     */
    public function withGradeLevel(string $gradeLevel): static
    {
        return $this->state(fn (array $attributes) => [
            'grade_level' => $gradeLevel,
        ]);
    }

    /**
     * Indicate that the student belongs to a specific age group.
     */
    public function withAgeGroup(string $ageGroup): static
    {
        return $this->state(fn (array $attributes) => [
            'age_group' => $ageGroup,
        ]);
    }

    /**
     * Indicate that the student has a specific gender.
     */
    public function withGender(string $gender): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => $gender,
        ]);
    }
}
