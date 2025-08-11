<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\StudentProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create main student if it doesn't exist
        $student = User::firstOrCreate(
            ['email' => 'student@sch.com'],
            [
                'name' => 'Student Ahmad',
                'email' => 'student@sch.com',
                'role' => 'student',
                'password' => Hash::make('123password'),
                'email_verified_at' => now(),
                'phone' => '+2348012345678',
                'location' => 'Lagos, Nigeria',
                'avatar' => null, // Will use initials system
            ]
        );
        
        // Create student profile if it doesn't exist
        if (!$student->studentProfile) {
            StudentProfile::create([
                'user_id' => $student->id,
                'date_of_birth' => '2010-05-15',
                'gender' => 'male',
                'status' => 'active',
                'registration_date' => now(),
                'grade_level' => 'Intermediate',
                'school_name' => 'Islamic Academy Lagos',
                'guardian_id' => null, // Will be updated later
                'learning_goals' => 'Complete Quran recitation with proper Tajweed and memorize selected surahs',
                'subjects_of_interest' => json_encode(['Tajweed', 'Hifz', 'Islamic Studies']),
                'preferred_learning_times' => json_encode(['Morning', 'Afternoon']),
                'age_group' => '13-15',
                'payment_id' => null,
            ]);
        }
        
        // Create additional student users using factory
        User::factory(30)->student()->create()->each(function ($user) {
            $gradeLevels = ['Beginner', 'Intermediate', 'Advanced', 'Expert'];
            $statuses = ['active', 'active', 'active', 'inactive']; // 75% active, 25% inactive
            $genders = ['male', 'female'];
            $ageGroups = ['5-8', '9-12', '13-15', '16-18', '19+'];
            
            StudentProfile::create([
                'user_id' => $user->id,
                'date_of_birth' => fake()->dateTimeBetween('-18 years', '-5 years'),
                'gender' => fake()->randomElement($genders),
                'status' => fake()->randomElement($statuses),
                'registration_date' => fake()->dateTimeBetween('-1 year', 'now'),
                'grade_level' => fake()->randomElement($gradeLevels),
                'school_name' => fake()->randomElement([
                    'Islamic Academy Lagos', 'Madrasah Al-Noor', 'Islamic Center Abuja',
                    'Quran Institute Kano', 'Islamic School Port Harcourt', 'Madrasah Al-Huda',
                    'Islamic Learning Center', 'Quran Academy', 'Islamic Education Institute'
                ]),
                'guardian_id' => null, // Will be assigned later
                'learning_goals' => fake()->randomElement([
                    'Learn proper Quran recitation with Tajweed',
                    'Memorize selected surahs and duas',
                    'Complete Quran memorization (Hifz)',
                    'Learn Islamic studies and history',
                    'Improve Arabic language skills',
                    'Learn Islamic etiquette and manners'
                ]),
                'subjects_of_interest' => json_encode(fake()->randomElements([
                    'Tajweed', 'Hifz', 'Islamic Studies', 'Tawheed', 'Qaida',
                    'Islamic History', 'Hadith Studies', 'Arabic Language', 'Islamic Etiquette'
                ], fake()->numberBetween(2, 4))),
                'preferred_learning_times' => json_encode(fake()->randomElements([
                    'Morning', 'Afternoon', 'Evening', 'Weekend'
                ], fake()->numberBetween(1, 3))),
                'age_group' => fake()->randomElement($ageGroups),
                'payment_id' => null,
            ]);
        });
    }
}
