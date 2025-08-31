<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\TeacherAvailability;

class TeacherAvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all teachers
        $teachers = User::where('role', 'teacher')->get();

        foreach ($teachers as $teacher) {
            // Create sample availability slots for each teacher
            $availabilities = [
                [
                    'day_of_week' => 1, // Monday
                    'start_time' => '10:00:00',
                    'end_time' => '11:00:00',
                    'availability_type' => 'Part-Time',
                    'is_active' => true,
                ],
                [
                    'day_of_week' => 1, // Monday
                    'start_time' => '15:00:00',
                    'end_time' => '16:00:00',
                    'availability_type' => 'Part-Time',
                    'is_active' => true,
                ],
                [
                    'day_of_week' => 1, // Monday
                    'start_time' => '17:00:00',
                    'end_time' => '18:00:00',
                    'availability_type' => 'Part-Time',
                    'is_active' => false, // This slot is unavailable
                ],
                [
                    'day_of_week' => 1, // Monday
                    'start_time' => '19:00:00',
                    'end_time' => '20:00:00',
                    'availability_type' => 'Part-Time',
                    'is_active' => true,
                ],
                [
                    'day_of_week' => 2, // Tuesday
                    'start_time' => '09:00:00',
                    'end_time' => '10:00:00',
                    'availability_type' => 'Part-Time',
                    'is_active' => true,
                ],
                [
                    'day_of_week' => 2, // Tuesday
                    'start_time' => '14:00:00',
                    'end_time' => '15:00:00',
                    'availability_type' => 'Part-Time',
                    'is_active' => true,
                ],
                [
                    'day_of_week' => 3, // Wednesday
                    'start_time' => '11:00:00',
                    'end_time' => '12:00:00',
                    'availability_type' => 'Part-Time',
                    'is_active' => true,
                ],
                [
                    'day_of_week' => 5, // Friday
                    'start_time' => '16:00:00',
                    'end_time' => '17:00:00',
                    'availability_type' => 'Part-Time',
                    'is_active' => true,
                ],
            ];

            foreach ($availabilities as $availability) {
                TeacherAvailability::create([
                    'teacher_id' => $teacher->id,
                    ...$availability
                ]);
            }
        }
    }
}