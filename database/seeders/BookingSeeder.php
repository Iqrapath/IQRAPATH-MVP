<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\TeachingSession;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Faker\Factory as FakerFactory;
use Illuminate\Support\Str;

class BookingSeeder extends Seeder
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
        
        // Create upcoming bookings (scheduled for future dates)
        $this->createUpcomingBookings($faker, $teachers, $students, $subjects);
        
        // Create ongoing bookings (sessions in progress)
        $this->createOngoingBookings($faker, $teachers, $students, $subjects);
        
        // Create completed bookings (past sessions)
        $this->createCompletedBookings($faker, $teachers, $students, $subjects);
        
        $this->command->info('Booking seeder completed successfully.');
    }
    
    /**
     * Create upcoming bookings (scheduled for future dates)
     */
    private function createUpcomingBookings($faker, $teachers, $students, $subjects): void
    {
        $this->command->info('Creating upcoming bookings...');
        
        for ($i = 0; $i < 8; $i++) {
            $teacher = $teachers->random();
            $student = $students->random();
            $subject = $subjects->random();
            
            // Future dates (1-30 days from now)
            $bookingDate = $faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d');
            $startTime = $faker->dateTimeBetween('08:00', '20:00')->format('H:i:s');
            $duration = $faker->numberBetween(30, 120);
            $endTime = date('H:i:s', strtotime($startTime) + $duration * 60);
            
            // Status for upcoming bookings
            $status = $faker->randomElement(['pending', 'approved', 'upcoming']);
            
            $booking = Booking::create([
                'booking_uuid' => Str::uuid(),
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => $subject->id,
                'booking_date' => $bookingDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_minutes' => $duration,
                'status' => $status,
                'notes' => $faker->optional(0.6)->sentence(),
                'created_by_id' => $student->id,
                'approved_by_id' => $status === 'approved' || $status === 'upcoming' ? $teacher->id : null,
                'approved_at' => $status === 'approved' || $status === 'upcoming' ? now()->subDays(rand(1, 7)) : null,
            ]);
            
            // Create teaching session for approved/upcoming bookings
            if (in_array($status, ['approved', 'upcoming'])) {
                $this->createTeachingSession($booking, 'scheduled', $faker);
            }
        }
    }
    
    /**
     * Create ongoing bookings (sessions currently in progress)
     */
    private function createOngoingBookings($faker, $teachers, $students, $subjects): void
    {
        $this->command->info('Creating ongoing bookings...');
        
        for ($i = 0; $i < 3; $i++) {
            $teacher = $teachers->random();
            $student = $students->random();
            $subject = $subjects->random();
            
            // Today's date
            $bookingDate = now()->format('Y-m-d');
            $startTime = $faker->dateTimeBetween('08:00', '20:00')->format('H:i:s');
            $duration = $faker->numberBetween(30, 120);
            $endTime = date('H:i:s', strtotime($startTime) + $duration * 60);
            
            $booking = Booking::create([
                'booking_uuid' => Str::uuid(),
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => $subject->id,
                'booking_date' => $bookingDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_minutes' => $duration,
                'status' => 'upcoming',
                'notes' => $faker->optional(0.6)->sentence(),
                'created_by_id' => $student->id,
                'approved_by_id' => $teacher->id,
                'approved_at' => now()->subDays(rand(1, 3)),
            ]);
            
            // Create teaching session in progress
            $this->createTeachingSession($booking, 'in_progress', $faker, true);
        }
    }
    
    /**
     * Create completed bookings (past sessions)
     */
    private function createCompletedBookings($faker, $teachers, $students, $subjects): void
    {
        $this->command->info('Creating completed bookings...');
        
        for ($i = 0; $i < 12; $i++) {
            $teacher = $teachers->random();
            $student = $students->random();
            $subject = $subjects->random();
            
            // Past dates (1-60 days ago)
            $bookingDate = $faker->dateTimeBetween('-60 days', '-1 day')->format('Y-m-d');
            $startTime = $faker->dateTimeBetween('08:00', '20:00')->format('H:i:s');
            $duration = $faker->numberBetween(30, 120);
            $endTime = date('H:i:s', strtotime($startTime) + $duration * 60);
            
            // Status for completed bookings
            $status = $faker->randomElement(['completed', 'completed', 'completed', 'cancelled', 'missed']);
            
            $booking = Booking::create([
                'booking_uuid' => Str::uuid(),
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => $subject->id,
                'booking_date' => $bookingDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_minutes' => $duration,
                'status' => $status,
                'notes' => $faker->optional(0.6)->sentence(),
                'created_by_id' => $student->id,
                'approved_by_id' => $teacher->id,
                'approved_at' => $faker->dateTimeBetween($bookingDate . ' -7 days', $bookingDate),
                'cancelled_by_id' => in_array($status, ['cancelled', 'missed']) ? $faker->randomElement([$student->id, $teacher->id]) : null,
                'cancelled_at' => in_array($status, ['cancelled', 'missed']) ? $faker->dateTimeBetween($bookingDate . ' -3 days', $bookingDate) : null,
            ]);
            
            // Create teaching session for all completed bookings
            $sessionStatus = $status === 'missed' ? 'no_show' : $status;
            $this->createTeachingSession($booking, $sessionStatus, $faker, false, true);
        }
    }
    
    /**
     * Create teaching session for a booking
     */
    private function createTeachingSession($booking, $status, $faker, $isOngoing = false, $isCompleted = false): void
    {
        $sessionData = [
            'session_uuid' => Str::uuid(),
            'booking_id' => $booking->id,
            'teacher_id' => $booking->teacher_id,
            'student_id' => $booking->student_id,
            'subject_id' => $booking->subject_id,
            'session_date' => $booking->booking_date,
            'start_time' => $booking->start_time,
            'end_time' => $booking->end_time,
            'status' => $status,
            'meeting_platform' => 'zoom',
            'zoom_meeting_id' => $faker->numerify('##########'),
            'zoom_host_id' => $faker->uuid,
            'zoom_join_url' => 'https://zoom.us/j/' . $faker->numerify('##########'),
            'zoom_start_url' => 'https://zoom.us/s/' . $faker->numerify('##########'),
            'zoom_password' => $faker->bothify('????####'),
        ];
        
        if ($isOngoing) {
            // Session is currently in progress
            $bookingDate = is_string($booking->booking_date) ? $booking->booking_date : $booking->booking_date->format('Y-m-d');
            $startTime = is_string($booking->start_time) ? $booking->start_time : $booking->start_time->format('H:i:s');
            $sessionDateTime = \Carbon\Carbon::parse($bookingDate . ' ' . $startTime);
            $sessionData['teacher_joined_at'] = $sessionDateTime->copy()->subMinutes(5); // 5 minutes before
            $sessionData['student_joined_at'] = $sessionDateTime->copy()->addMinute(); // 1 minute after
            $sessionData['teacher_marked_present'] = true;
            $sessionData['student_marked_present'] = true;
            $sessionData['attendance_data'] = json_encode([
                'teacher_attendance_percentage' => 100,
                'student_attendance_percentage' => $faker->numberBetween(85, 100),
            ]);
        } elseif ($isCompleted) {
            // Session is completed
            $bookingDate = is_string($booking->booking_date) ? $booking->booking_date : $booking->booking_date->format('Y-m-d');
            $startTime = is_string($booking->start_time) ? $booking->start_time : $booking->start_time->format('H:i:s');
            $sessionDateTime = \Carbon\Carbon::parse($bookingDate . ' ' . $startTime);
            $duration = $booking->duration_minutes;
            
            if ($status === 'completed') {
                $sessionData['actual_duration_minutes'] = $duration - $faker->numberBetween(0, 10);
                $sessionData['teacher_joined_at'] = $sessionDateTime->copy()->subMinutes(5);
                $sessionData['student_joined_at'] = $sessionDateTime->copy()->addMinute();
                $sessionData['teacher_left_at'] = $sessionDateTime->copy()->addMinutes($duration);
                $sessionData['student_left_at'] = $sessionDateTime->copy()->addMinutes($duration - 2);
                $sessionData['completion_date'] = $sessionDateTime->copy()->addMinutes($duration);
                $sessionData['teacher_marked_present'] = true;
                $sessionData['student_marked_present'] = true;
                $sessionData['recording_url'] = $faker->url;
                $sessionData['teacher_notes'] = $faker->paragraph;
                $sessionData['student_notes'] = $faker->optional(0.7)->paragraph;
                $sessionData['teacher_rating'] = $faker->numberBetween(4, 5);
                $sessionData['student_rating'] = $faker->numberBetween(4, 5);
                $sessionData['attendance_data'] = json_encode([
                    'teacher_attendance_percentage' => 100,
                    'student_attendance_percentage' => $faker->numberBetween(85, 100),
                ]);
            } else {
                // Cancelled or missed
                $sessionData['teacher_marked_present'] = false;
                $sessionData['student_marked_present'] = false;
                $sessionData['attendance_data'] = json_encode([
                    'teacher_attendance_percentage' => 0,
                    'student_attendance_percentage' => 0,
                ]);
            }
        }
        
        TeachingSession::create($sessionData);
    }
}
