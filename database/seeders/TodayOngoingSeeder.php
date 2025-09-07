<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\TeachingSession;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Faker\Factory as FakerFactory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TodayOngoingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = FakerFactory::create();
        
        // Get users and subjects
        $teachers = User::where('role', 'teacher')->get();
        $students = User::where('role', 'student')->get();
        $subjects = Subject::all();
        
        if ($teachers->isEmpty() || $students->isEmpty() || $subjects->isEmpty()) {
            $this->command->info('Missing required data. Please run UserSeeder and SubjectTemplatesSeeder first.');
            return;
        }
        
        // Create 2 ongoing bookings for today (ensure at least one is for the first student)
        $firstStudent = $students->first(); // Get the first student to ensure they have ongoing classes
        
        for ($i = 0; $i < 2; $i++) {
            $teacher = $teachers->random();
            $student = $i === 0 ? $firstStudent : $students->random(); // First ongoing class for first student
            $subject = $subjects->random();
            
            // Today's date
            $today = Carbon::today();
            $now = Carbon::now();
            
            // Create ongoing classes: start time is 10 minutes ago, end time is 3 hours from now
            // This ensures the class is always "ongoing" for testing purposes
            $startTime = $now->copy()->subMinutes(10)->format('H:i:s');
            $endTime = $now->copy()->addHours(3)->format('H:i:s'); // 3 hours from now
            $duration = 190; // 3 hours 10 minutes total duration
            
            $booking = Booking::create([
                'booking_uuid' => Str::uuid(),
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => $subject->id,
                'booking_date' => $today->format('Y-m-d'),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_minutes' => $duration,
                'status' => 'upcoming', // This makes it "ongoing" when current time is between start and end
                'notes' => 'Ongoing session for testing',
                'created_by_id' => $student->id,
                'approved_by_id' => $teacher->id,
                'approved_at' => $today->subDay(),
            ]);
            
            // Create teaching session in in_progress status
            TeachingSession::create([
                'session_uuid' => Str::uuid(),
                'booking_id' => $booking->id,
                'teacher_id' => $booking->teacher_id,
                'student_id' => $booking->student_id,
                'subject_id' => $booking->subject_id,
                'session_date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'status' => 'in_progress', // This indicates the session is currently ongoing
                'meeting_platform' => 'zoom',
                'zoom_meeting_id' => $faker->numerify('##########'),
                'zoom_host_id' => $faker->uuid,
                'zoom_join_url' => 'https://zoom.us/j/' . $faker->numerify('##########'),
                'zoom_start_url' => 'https://zoom.us/s/' . $faker->numerify('##########'),
                'zoom_password' => $faker->bothify('????####'),
                'teacher_joined_at' => $now->copy()->subMinutes(35),
                'student_joined_at' => $now->copy()->subMinutes(25),
            ]);
            
            $this->command->info("Created ongoing booking for today: {$today->format('Y-m-d')} {$startTime} - {$endTime}");
        }
        
        $this->command->info('Today ongoing booking seeder completed successfully.');
    }
}
