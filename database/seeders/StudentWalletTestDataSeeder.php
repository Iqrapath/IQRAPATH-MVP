<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Subject;
use App\Models\Booking;
use App\Models\TeacherProfile;
use Illuminate\Support\Str;

class StudentWalletTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a student
        $student = User::where('role', 'student')->where('email', 'student@sch.com')->first();
        
        if (!$student) {
            $student = User::create([
                'name' => 'Test Student',
                'email' => 'student@sch.com',
                'password' => bcrypt('password'),
                'role' => 'student',
                'account_status' => 'active',
                'email_verified_at' => now(),
            ]);
            
            // Create student profile
            $student->studentProfile()->create([
                'date_of_birth' => '2005-01-15',
                'grade_level' => 'Grade 10',
                'school_name' => 'Test School',
                'learning_goals' => 'Learn Quran and Arabic',
            ]);
            
            // Create student wallet
            $student->studentWallet()->create([
                'balance' => 50000.00,
                'total_spent' => 25000.00,
                'total_refunded' => 0.00,
            ]);
        }

        // Get or create teachers
        $teachers = [];
        $teacherData = [
            [
                'name' => 'Sheikh Ahmed Hassan',
                'email' => 'ahmed.hassan@teacher.com',
                'hourly_rate' => 5000,
            ],
            [
                'name' => 'Ustadh Ibrahim Yusuf',
                'email' => 'ibrahim.yusuf@teacher.com',
                'hourly_rate' => 4500,
            ],
            [
                'name' => 'Ustadha Fatima Zahra',
                'email' => 'fatima.zahra@teacher.com',
                'hourly_rate' => 6000,
            ],
        ];

        foreach ($teacherData as $data) {
            $teacher = User::where('email', $data['email'])->first();
            
            if (!$teacher) {
                $teacher = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => bcrypt('password'),
                    'role' => 'teacher',
                    'account_status' => 'active',
                    'email_verified_at' => now(),
                ]);
                
                // Create teacher profile
                $teacher->teacherProfile()->create([
                    'bio' => 'Experienced Islamic teacher',
                    'qualifications' => 'Bachelor in Islamic Studies',
                    'teaching_experience_years' => 5,
                    'hourly_rate_ngn' => $data['hourly_rate'],
                    'hourly_rate_usd' => $data['hourly_rate'] / 1500,
                    'preferred_currency' => 'NGN',
                    'is_verified' => true,
                    'verification_status' => 'approved',
                ]);
                
                // Create teacher wallet
                $teacher->teacherWallet()->create([
                    'available_balance' => 0.00,
                    'pending_balance' => 0.00,
                    'total_withdrawn' => 0.00,
                ]);
            }
            
            $teachers[] = [
                'user' => $teacher,
                'hourly_rate' => $data['hourly_rate'],
            ];
        }

        // Get or create subjects
        $subjects = [];
        $subjectNames = [
            'Quran Recitation (Tajweed)',
            'Arabic Language',
            'Islamic Studies',
            'Quran Memorization (Hifz)',
        ];

        foreach ($subjectNames as $name) {
            $subject = Subject::firstOrCreate(
                ['name' => $name],
                [
                    'description' => "Learn {$name} with qualified teachers",
                    'is_active' => true,
                ]
            );
            $subjects[] = $subject;
        }

        // Create upcoming bookings with different statuses
        $bookingsData = [
            // Pending booking - needs approval
            [
                'teacher' => $teachers[0],
                'subject' => $subjects[0],
                'status' => 'pending',
                'booking_date' => now()->addDays(2),
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'duration_minutes' => 60,
                'notes' => 'First Tajweed lesson - need to learn proper pronunciation',
            ],
            // Approved booking - waiting for payment
            [
                'teacher' => $teachers[1],
                'subject' => $subjects[1],
                'status' => 'approved',
                'booking_date' => now()->addDays(3),
                'start_time' => '14:00:00',
                'end_time' => '15:30:00',
                'duration_minutes' => 90,
                'notes' => 'Arabic grammar lesson',
            ],
            // Upcoming booking - confirmed and paid
            [
                'teacher' => $teachers[2],
                'subject' => $subjects[2],
                'status' => 'upcoming',
                'booking_date' => now()->addDays(5),
                'start_time' => '16:00:00',
                'end_time' => '17:00:00',
                'duration_minutes' => 60,
                'notes' => 'Islamic history and fiqh',
            ],
            // Another pending booking
            [
                'teacher' => $teachers[0],
                'subject' => $subjects[3],
                'status' => 'pending',
                'booking_date' => now()->addDays(7),
                'start_time' => '09:00:00',
                'end_time' => '10:30:00',
                'duration_minutes' => 90,
                'notes' => 'Quran memorization - Surah Al-Baqarah',
            ],
            // Approved booking for next week
            [
                'teacher' => $teachers[1],
                'subject' => $subjects[0],
                'status' => 'approved',
                'booking_date' => now()->addDays(10),
                'start_time' => '11:00:00',
                'end_time' => '12:00:00',
                'duration_minutes' => 60,
                'notes' => 'Advanced Tajweed rules',
            ],
        ];

        foreach ($bookingsData as $data) {
            $teacher = $data['teacher']['user'];
            $hourlyRate = $data['teacher']['hourly_rate'];
            
            Booking::create([
                'booking_uuid' => Str::uuid(),
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => $data['subject']->id,
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'duration_minutes' => $data['duration_minutes'],
                'status' => $data['status'],
                'notes' => $data['notes'],
                'created_by_id' => $student->id,
                'approved_by_id' => $data['status'] === 'approved' || $data['status'] === 'upcoming' ? $teacher->id : null,
                'approved_at' => $data['status'] === 'approved' || $data['status'] === 'upcoming' ? now() : null,
                'hourly_rate_ngn' => $hourlyRate,
                'hourly_rate_usd' => $hourlyRate / 1500,
                'rate_currency' => 'NGN',
                'exchange_rate_used' => 1500.00,
                'rate_locked_at' => now(),
            ]);
        }

        $this->command->info('✓ Created test student: ' . $student->email);
        $this->command->info('✓ Created ' . count($teachers) . ' teachers');
        $this->command->info('✓ Created ' . count($subjects) . ' subjects');
        $this->command->info('✓ Created ' . count($bookingsData) . ' upcoming bookings');
        $this->command->info('');
        $this->command->info('Booking Summary:');
        $this->command->info('- Pending bookings: 2 (need teacher approval)');
        $this->command->info('- Approved bookings: 2 (approved, waiting for payment)');
        $this->command->info('- Upcoming bookings: 1 (confirmed and paid)');
        $this->command->info('');
        $this->command->info('Login as: student@sch.com / password');
        $this->command->info('Navigate to: /student/wallet');
    }
}
