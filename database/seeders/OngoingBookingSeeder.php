<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\TeachingSession;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Faker\Factory as FakerFactory;
use Illuminate\Support\Str;

class OngoingBookingSeeder extends Seeder
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
        
        // Create 2-3 ongoing bookings for today
        for ($i = 0; $i < 3; $i++) {
            $teacher = $teachers->random();
            $student = $students->random();
            $subject = $subjects->random();
            
            // Today's date
            $bookingDate = now()->format('Y-m-d');
            
            // Create ongoing classes: start time is in the past, end time is in the future
            $currentTime = now();
            $startTime = $currentTime->subMinutes(rand(15, 45))->format('H:i:s'); // Started 15-45 minutes ago
            $duration = $faker->numberBetween(60, 120); // 1-2 hours duration
            $endTime = $currentTime->addMinutes($duration)->format('H:i:s'); // Ends in the future
            
            $booking = Booking::create([
                'booking_uuid' => Str::uuid(),
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => $subject->id,
                'booking_date' => $bookingDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_minutes' => $duration,
                'status' => 'upcoming', // This makes it "ongoing" when current time is between start and end
                'notes' => $faker->optional(0.6)->sentence(),
                'created_by_id' => $student->id,
                'approved_by_id' => $teacher->id,
                'approved_at' => now()->subDays(rand(1, 3)),
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
                'teacher_joined_at' => $currentTime->subMinutes(rand(10, 30)),
                'student_joined_at' => $currentTime->subMinutes(rand(5, 20)),
            ]);
        }
        
        $this->command->info('Ongoing booking seeder completed successfully.');
    }
}
