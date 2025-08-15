<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TeachingSession;
use App\Models\User;
use App\Models\Subject;

class CompletedClassesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some teachers and students
        $teachers = User::where('role', 'teacher')->take(3)->get();
        $students = User::where('role', 'student')->take(5)->get();
        $subjects = Subject::take(3)->get();
        
        if ($teachers->isEmpty() || $students->isEmpty() || $subjects->isEmpty()) {
            $this->command->error('Need teachers, students, and subjects to create completed classes.');
            return;
        }

        $completedClasses = [
            [
                'session_date' => now()->subDays(1)->format('Y-m-d'),
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'actual_duration_minutes' => 60,
                'completion_date' => now()->subDays(1)->setTime(10, 0, 0),
                'attendance_count' => 2,
                'teacher_rating' => 4.5,
                'student_rating' => 4.8,
                'notifications_sent_count' => 3,
                'notification_history' => [
                    ['type' => 'reminder', 'status' => 'sent', 'sent_at' => now()->subDays(1)->setTime(8, 0, 0)->toISOString()],
                    ['type' => 'start_reminder', 'status' => 'sent', 'sent_at' => now()->subDays(1)->setTime(8, 55, 0)->toISOString()],
                    ['type' => 'completion_summary', 'status' => 'sent', 'sent_at' => now()->subDays(1)->setTime(10, 5, 0)->toISOString()],
                ],
                'status' => 'completed',
                'teacher_marked_present' => true,
                'student_marked_present' => true,
            ],
            [
                'session_date' => now()->subDays(2)->format('Y-m-d'),
                'start_time' => '14:00:00',
                'end_time' => '15:30:00',
                'actual_duration_minutes' => 90,
                'completion_date' => now()->subDays(2)->setTime(15, 30, 0),
                'attendance_count' => 2,
                'teacher_rating' => 4.2,
                'student_rating' => 4.0,
                'notifications_sent_count' => 4,
                'notification_history' => [
                    ['type' => 'reminder', 'status' => 'sent', 'sent_at' => now()->subDays(2)->setTime(13, 0, 0)->toISOString()],
                    ['type' => 'start_reminder', 'status' => 'sent', 'sent_at' => now()->subDays(2)->setTime(13, 55, 0)->toISOString()],
                    ['type' => 'completion_summary', 'status' => 'sent', 'sent_at' => now()->subDays(2)->setTime(15, 35, 0)->toISOString()],
                    ['type' => 'feedback_request', 'status' => 'sent', 'sent_at' => now()->subDays(2)->setTime(16, 0, 0)->toISOString()],
                ],
                'status' => 'completed',
                'teacher_marked_present' => true,
                'student_marked_present' => true,
            ],
            [
                'session_date' => now()->subDays(3)->format('Y-m-d'),
                'start_time' => '16:00:00',
                'end_time' => '17:00:00',
                'actual_duration_minutes' => 55,
                'completion_date' => now()->subDays(3)->setTime(16, 55, 0),
                'attendance_count' => 1,
                'teacher_rating' => 3.8,
                'student_rating' => null,
                'notifications_sent_count' => 2,
                'notification_history' => [
                    ['type' => 'reminder', 'status' => 'sent', 'sent_at' => now()->subDays(3)->setTime(15, 0, 0)->toISOString()],
                    ['type' => 'completion_summary', 'status' => 'sent', 'sent_at' => now()->subDays(3)->setTime(16, 55, 0)->toISOString()],
                ],
                'status' => 'completed',
                'teacher_marked_present' => true,
                'student_marked_present' => false,
            ],
            [
                'session_date' => now()->subDays(5)->format('Y-m-d'),
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'actual_duration_minutes' => 60,
                'completion_date' => now()->subDays(5)->setTime(11, 0, 0),
                'attendance_count' => 2,
                'teacher_rating' => 4.7,
                'student_rating' => 4.9,
                'notifications_sent_count' => 3,
                'notification_history' => [
                    ['type' => 'reminder', 'status' => 'sent', 'sent_at' => now()->subDays(5)->setTime(9, 0, 0)->toISOString()],
                    ['type' => 'start_reminder', 'status' => 'sent', 'sent_at' => now()->subDays(5)->setTime(9, 55, 0)->toISOString()],
                    ['type' => 'completion_summary', 'status' => 'sent', 'sent_at' => now()->subDays(5)->setTime(11, 5, 0)->toISOString()],
                ],
                'status' => 'completed',
                'teacher_marked_present' => true,
                'student_marked_present' => true,
            ],
            [
                'session_date' => now()->subDays(7)->format('Y-m-d'),
                'start_time' => '13:00:00',
                'end_time' => '14:00:00',
                'actual_duration_minutes' => 60,
                'completion_date' => now()->subDays(7)->setTime(14, 0, 0),
                'attendance_count' => 2,
                'teacher_rating' => 4.0,
                'student_rating' => 4.2,
                'notifications_sent_count' => 3,
                'notification_history' => [
                    ['type' => 'reminder', 'status' => 'sent', 'sent_at' => now()->subDays(7)->setTime(12, 0, 0)->toISOString()],
                    ['type' => 'start_reminder', 'status' => 'sent', 'sent_at' => now()->subDays(7)->setTime(12, 55, 0)->toISOString()],
                    ['type' => 'completion_summary', 'status' => 'sent', 'sent_at' => now()->subDays(7)->setTime(14, 5, 0)->toISOString()],
                ],
                'status' => 'completed',
                'teacher_marked_present' => true,
                'student_marked_present' => true,
            ],
        ];

        foreach ($completedClasses as $index => $classData) {
            $teacher = $teachers[$index % $teachers->count()];
            $student = $students[$index % $students->count()];
            $subject = $subjects[$index % $subjects->count()];

            TeachingSession::create(array_merge($classData, [
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'subject_id' => $subject->id,
            ]));
        }

        $this->command->info('Completed classes seeded successfully!');
    }
}
