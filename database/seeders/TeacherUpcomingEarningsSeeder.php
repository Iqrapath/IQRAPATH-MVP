<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeacherUpcomingEarningsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get teacher with ID 14 (Ahmad Ali)
        $teacherId = 14;
        
        // Get a student
        $student = DB::table('users')->where('role', 'student')->first();
        
        if (!$student) {
            $this->command->error('No student found in database');
            return;
        }
        
        // Get a subject
        $subject = DB::table('subjects')->first();
        
        if (!$subject) {
            $this->command->error('No subject found in database');
            return;
        }
        
        // Create 3 scheduled teaching sessions for upcoming earnings
        $sessions = [
            [
                'session_uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'teacher_id' => $teacherId,
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'session_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'session_uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'teacher_id' => $teacherId,
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'session_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
                'start_time' => '14:00:00',
                'end_time' => '15:30:00',
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'session_uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'teacher_id' => $teacherId,
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'session_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        foreach ($sessions as $session) {
            DB::table('teaching_sessions')->insert($session);
        }
        
        $this->command->info('✅ Created 3 scheduled teaching sessions for teacher ID: ' . $teacherId);
        $this->command->info('   - Session 1: ' . Carbon::now()->addDays(2)->format('M d, Y') . ' at 10:00-11:00');
        $this->command->info('   - Session 2: ' . Carbon::now()->addDays(5)->format('M d, Y') . ' at 14:00-15:30');
        $this->command->info('   - Session 3: ' . Carbon::now()->addDays(7)->format('M d, Y') . ' at 09:00-10:00');
        $this->command->info('');
        $this->command->info('These sessions will now appear in "Upcoming Earning Due" section');
    }
}
