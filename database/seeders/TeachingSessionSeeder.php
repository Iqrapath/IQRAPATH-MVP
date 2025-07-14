<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\StudentProfile;
use App\Models\TeachingSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as FakerFactory;
use Illuminate\Support\Str;

class TeachingSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = FakerFactory::create();
        
        // Get teacher users
        $teachers = User::where('role', 'teacher')->get();
        if ($teachers->isEmpty()) {
            $this->command->info('No teacher users found. Please run the UserSeeder first.');
            return;
        }
        
        // Get student users
        $students = User::where('role', 'student')->get();
        if ($students->isEmpty()) {
            $this->command->info('No student users found. Please run the UserSeeder first.');
            return;
        }
        
        // Get or create subjects
        $subjects = Subject::all();
        if ($subjects->isEmpty()) {
            $subjects = $this->createSubjects($teachers->first()->id);
        }
        
        // Create completed one-to-one sessions
        $this->createCompletedSessions($faker, $teachers, $students, $subjects);
    }
    
    /**
     * Create subjects if they don't exist.
     */
    private function createSubjects($teacherId)
    {
        $subjects = [
            ['name' => 'Tajweed', 'teacher_profile_id' => $teacherId],
            ['name' => 'Quran', 'teacher_profile_id' => $teacherId],
            ['name' => 'Arabic', 'teacher_profile_id' => $teacherId],
            ['name' => 'Islamic Studies', 'teacher_profile_id' => $teacherId],
            ['name' => 'Fiqh', 'teacher_profile_id' => $teacherId],
        ];
        
        $createdSubjects = collect();
        
        foreach ($subjects as $subject) {
            $createdSubjects->push(Subject::create($subject));
        }
        
        return $createdSubjects;
    }
    
    /**
     * Create completed teaching sessions.
     */
    private function createCompletedSessions($faker, $teachers, $students, $subjects)
    {
        // Create 15 completed sessions
        for ($i = 0; $i < 15; $i++) {
            $teacher = $teachers->random();
            $student = $students->random();
            $subject = $subjects->random();
            
            // Session date and times
            $sessionDate = $faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d');
            $startTime = $faker->dateTimeBetween('08:00', '20:00')->format('H:i:s');
            $duration = $faker->numberBetween(30, 120);
            $endTime = date('H:i:s', strtotime($startTime) + $duration * 60);
            
            // Status - mostly completed, some cancelled or no_show
            $status = $faker->randomElement(['completed', 'completed', 'completed', 'completed', 'cancelled', 'no_show']);
            
            // Generate attendance data based on status
            $teacherJoined = null;
            $studentJoined = null;
            $teacherLeft = null;
            $studentLeft = null;
            $actualDuration = null;
            
            if ($status === 'completed') {
                $sessionDateTime = $sessionDate . ' ' . $startTime;
                $teacherJoined = date('Y-m-d H:i:s', strtotime($sessionDateTime) - 300); // 5 minutes before
                $studentJoined = date('Y-m-d H:i:s', strtotime($sessionDateTime) + 60); // 1 minute after
                $teacherLeft = date('Y-m-d H:i:s', strtotime($sessionDateTime) + $duration * 60);
                $studentLeft = date('Y-m-d H:i:s', strtotime($sessionDateTime) + $duration * 60 - 120); // 2 minutes before end
                $actualDuration = $duration - 3; // Slightly less than planned
            }
            
            TeachingSession::create([
                'session_uuid' => Str::uuid(),
                'booking_id' => null,
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'session_date' => $sessionDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'actual_duration_minutes' => $actualDuration,
                'status' => $status,
                'meeting_link' => $status !== 'no_show' ? 'https://zoom.us/j/' . $faker->numerify('##########') : null,
                'meeting_platform' => 'zoom',
                'meeting_password' => $faker->bothify('????####'),
                'zoom_meeting_id' => $faker->numerify('##########'),
                'zoom_host_id' => $faker->uuid,
                'zoom_join_url' => 'https://zoom.us/j/' . $faker->numerify('##########'),
                'zoom_start_url' => 'https://zoom.us/s/' . $faker->numerify('##########'),
                'zoom_password' => $faker->bothify('????####'),
                'teacher_marked_present' => $status === 'completed',
                'student_marked_present' => $status === 'completed',
                'attendance_data' => json_encode([
                    'teacher_attendance_percentage' => $status === 'completed' ? 100 : 0,
                    'student_attendance_percentage' => $status === 'completed' ? $faker->numberBetween(85, 100) : 0,
                ]),
                'teacher_joined_at' => $teacherJoined,
                'student_joined_at' => $studentJoined,
                'teacher_left_at' => $teacherLeft,
                'student_left_at' => $studentLeft,
                'recording_url' => $status === 'completed' ? $faker->url : null,
                'teacher_notes' => $status === 'completed' ? $faker->paragraph : null,
                'student_notes' => $status === 'completed' && $faker->boolean(70) ? $faker->paragraph : null,
            ]);
        }
    }
} 