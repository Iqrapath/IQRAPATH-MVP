<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\TeacherProfile;
use App\Models\Subject;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create main teacher if it doesn't exist
        $mainTeacher = User::firstOrCreate(
            ['email' => 'teacher@sch.com'],
            [
                'name' => 'Teacher Ahmad Ali',
                'email' => 'teacher@sch.com',
                'role' => 'teacher',
                'password' => Hash::make('123password'),
                'email_verified_at' => now(),
                'location' => 'Lagos, Nigeria',
                'phone' => '+2348012345678',
                'avatar' => null, // Will use initials system
            ]
        );
        
        // Create teacher profile if it doesn't exist
        if (!$mainTeacher->teacherProfile) {
            $teacherProfile = TeacherProfile::create([
                'user_id' => $mainTeacher->id,
                'bio' => 'Experienced Quran teacher with over 10 years of experience in Tajweed and Hifz. Specialized in teaching children and adults with proper pronunciation and memorization techniques.',
                'experience_years' => '10',
                'verified' => true,
                'languages' => json_encode(['English', 'Arabic', 'Hausa']),
                'teaching_type' => 'Online',
                'teaching_mode' => 'One-to-One',
                'education' => 'Al-Azhar University',
                'qualification' => 'Quran Memorization Certificate',
                'rating' => 4.8,
                'reviews_count' => 45,
                'join_date' => now()->subMonths(8),
                'hourly_rate_usd' => 35.00,
                'hourly_rate_ngn' => 42000.00,
                'intro_video_url' => null, // Will be uploaded by teacher
            ]);

            // Create subjects for the main teacher
            $mainTeacherSubjects = ['Tajweed', 'Hifz', 'Quran Recitation', 'Islamic Studies'];
            foreach ($mainTeacherSubjects as $subjectName) {
                Subject::create([
                    'teacher_profile_id' => $teacherProfile->id,
                    'name' => $subjectName,
                    'is_active' => true,
                ]);
            }
        }

        // Create specific teachers with known data
        $specificTeachers = [
            [
                'name' => 'Sheikh Abdul Rahman',
                'email' => 'abdul.rahman@iqrapath.com',
                'phone' => '+2348012345678',
                'location' => 'Cairo, Egypt',
                'role' => 'teacher',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'avatar' => 'https://plus.unsplash.com/premium_photo-1664301632032-6c690ba2d240?q=80&w=687&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
                'profile' => [
                    'bio' => 'Experienced Quran teacher with over 10 years of teaching experience. Specialized in Tajweed and Hifz.',
                    'experience_years' => '10',
                    'verified' => true,
                    'languages' => ['English', 'Arabic', 'French'],
                    'teaching_type' => 'Online',
                    'teaching_mode' => 'One-to-One',
                    'rating' => 4.95,
                    'reviews_count' => 127,
                    'education' => 'Al-Azhar University',
                    'qualification' => 'Quran Memorization Certificate',
                    'hourly_rate_usd' => 95.00,
                    'hourly_rate_ngn' => 47500.00,
                ],
                'subjects' => ['Tajweed', 'Hifz', 'Quran Recitation', 'Islamic Studies']
            ],
            [
                'name' => 'Ustadah Aisha Bello',
                'email' => 'aisha.bello@iqrapath.com',
                'phone' => '+2348023456789',
                'location' => 'Abuja, Nigeria',
                'role' => 'teacher',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'avatar' => 'https://plus.unsplash.com/premium_photo-1661499767763-351b5b894985?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
                'profile' => [
                    'bio' => 'Dedicated female Quran teacher with expertise in teaching children and beginners.',
                    'experience_years' => '7',
                    'verified' => true,
                    'languages' => ['English', 'Arabic', 'Hausa'],
                    'teaching_type' => 'Online',
                    'teaching_mode' => 'One-to-One',
                    'rating' => 4.75,
                    'reviews_count' => 89,
                    'education' => 'Islamic University of Madinah',
                    'qualification' => 'Islamic Studies Degree',
                    'hourly_rate_usd' => 45.00,
                    'hourly_rate_ngn' => 22500.00,
                ],
                'subjects' => ['Tajweed', 'Tawheed', 'Qaida', 'Islamic History']
            ],
            [
                'name' => 'Ustadh Musa Khalid',
                'email' => 'musa.khalid@iqrapath.com',
                'phone' => '+971501234567',
                'location' => 'Dubai, UAE',
                'role' => 'teacher',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'avatar' => 'https://plus.unsplash.com/premium_photo-1663039980809-1eed880ca36e?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
                'profile' => [
                    'bio' => 'Professional Quran teacher with international experience. Expert in advanced Tajweed rules.',
                    'experience_years' => '12',
                    'verified' => true,
                    'languages' => ['English', 'Arabic', 'Urdu'],
                    'teaching_type' => 'Online',
                    'teaching_mode' => 'One-to-One',
                    'rating' => 4.95,
                    'reviews_count' => 156,
                    'education' => 'Umm Al-Qura University',
                    'qualification' => 'Quran Sciences Certificate',
                    'hourly_rate_usd' => 65.00,
                    'hourly_rate_ngn' => 32500.00,
                ],
                'subjects' => ['Quran Recitation', 'Hifz', 'Advanced Tajweed', 'Tafsir']
            ],
            [
                'name' => 'Ustadh Ibrahim Hassan',
                'email' => 'ibrahim.hassan@iqrapath.com',
                'phone' => '+2348034567890',
                'location' => 'Lagos, Nigeria',
                'role' => 'teacher',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'avatar' => 'https://plus.unsplash.com/premium_photo-1682144174635-6505d6212267?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
                'profile' => [
                    'bio' => 'Passionate teacher specializing in teaching Quran to children and adults with learning difficulties.',
                    'experience_years' => '8',
                    'verified' => true,
                    'languages' => ['English', 'Arabic', 'Yoruba'],
                    'teaching_type' => 'Online',
                    'teaching_mode' => 'One-to-One',
                    'rating' => 4.80,
                    'reviews_count' => 94,
                    'education' => 'Bayero University Kano',
                    'qualification' => 'Islamic Studies Degree',
                    'hourly_rate_usd' => 55.00,
                    'hourly_rate_ngn' => 27500.00,
                ],
                'subjects' => ['Qaida', 'Tajweed', 'Islamic Studies', 'Arabic Language']
            ],
            [
                'name' => 'Ustadah Fatima Zahra',
                'email' => 'fatima.zahra@iqrapath.com',
                'phone' => '+2348045678901',
                'location' => 'Kano, Nigeria',
                'role' => 'teacher',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'avatar' => 'https://plus.unsplash.com/premium_photo-1661458052153-81af30f312d3?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
                'profile' => [
                    'bio' => 'Experienced female teacher with expertise in teaching Quran memorization and Islamic etiquette.',
                    'experience_years' => '9',
                    'verified' => true,
                    'languages' => ['English', 'Arabic', 'Hausa'],
                    'teaching_type' => 'Online',
                    'teaching_mode' => 'One-to-One',
                    'rating' => 4.85,
                    'reviews_count' => 112,
                    'education' => 'Islamic University of Madinah',
                    'qualification' => 'Quran Memorization Certificate',
                    'hourly_rate_usd' => 75.00,
                    'hourly_rate_ngn' => 37500.00,
                ],
                'subjects' => ['Hifz', 'Tajweed', 'Islamic Etiquette', 'Hadith Studies']
            ]
        ];

        // Create specific teachers
        foreach ($specificTeachers as $teacherData) {
            $subjects = $teacherData['subjects'];
            unset($teacherData['subjects']);
            $profileData = $teacherData['profile'];
            unset($teacherData['profile']);

            // Create user
            $user = User::create($teacherData);

            // Create teacher profile
            $profile = TeacherProfile::create([
                'user_id' => $user->id,
                ...$profileData
            ]);

            // Create subjects for the teacher
            foreach ($subjects as $subjectName) {
                Subject::create([
                    'teacher_profile_id' => $profile->id,
                    'name' => $subjectName,
                    'is_active' => true,
                ]);
            }
        }

        // Create additional random teachers using factory
        User::factory(25)->teacher()->create()->each(function ($user) {
            $teacherProfile = TeacherProfile::factory()->create([
                'user_id' => $user->id,
            ]);

            // Create subjects for this teacher
            $subjectNames = [
                'Tajweed', 'Hifz', 'Quran Recitation', 'Islamic Studies', 
                'Tawheed', 'Qaida', 'Islamic History', 'Advanced Tajweed', 
                'Tafsir', 'Islamic Etiquette', 'Hadith Studies', 'Arabic Language'
            ];
            
            $randomSubjects = fake()->randomElements($subjectNames, fake()->numberBetween(2, 5));
            
            foreach ($randomSubjects as $subjectName) {
                Subject::create([
                    'teacher_profile_id' => $teacherProfile->id,
                    'name' => $subjectName,
                    'is_active' => true,
                ]);
            }
        });
    }
}
