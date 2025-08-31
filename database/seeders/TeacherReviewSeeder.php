<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\TeacherReview;
use App\Models\TeachingSession;

class TeacherReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
                // Get teachers and students
        $teachers = User::where('role', 'teacher')->get();
        $students = User::where('role', 'student')->get();

        // Check if we have students, if not, create some or skip
        if ($students->isEmpty()) {
            $this->command->info('No students found. Skipping review seeding.');
            return;
        }

        // Check if we have subjects
        $firstSubjects = \App\Models\Subject::all();
        if ($firstSubjects->isEmpty()) {
            $this->command->info('No subjects found. Skipping review seeding.');
            return;
        }

        // Sample review texts
        $reviewTexts = [
            'Very patient and explains concepts clearly. Highly recommended!',
            'Excellent teacher! Made learning Quran very easy and enjoyable.',
            'Great knowledge of Tajweed rules and very encouraging.',
            'Patient with my pronunciation and always corrects me kindly.',
            'Amazing teacher! My child loves the lessons.',
            'Very knowledgeable and makes classes interactive.',
            'Fantastic teacher who goes above and beyond.',
            'Clear explanations and very supportive throughout.',
            'Highly skilled in teaching Arabic and Quran.',
            'Professional and punctual. Great experience overall.',
            'Makes learning fun and engaging for students.',
            'Very understanding and adapts to student pace.',
            'Excellent methodology and very patient.',
            'Great teacher with deep knowledge of Islamic studies.',
            'Inspiring teacher who motivates students to learn more.',
        ];

        $studentNames = $students->pluck('name')->toArray();
        $defaultSubjectId = $firstSubjects->first()->id; // Store the subject ID for use in loop

        foreach ($teachers as $teacher) {
            // Create 3-8 reviews per teacher
            $reviewCount = rand(3, 8);
            
            for ($i = 0; $i < $reviewCount; $i++) {
                $student = $students->random();
                
                // Skip if review already exists for this teacher-student pair
                $existingReview = TeacherReview::where('teacher_id', $teacher->id)
                    ->where('student_id', $student->id)
                    ->first();
                    
                if ($existingReview) {
                    continue;
                }

                // Find or create a mock teaching session
                $sessionDate = now()->subDays(rand(1, 60));
                $session = TeachingSession::firstOrCreate([
                    'teacher_id' => $teacher->id,
                    'student_id' => $student->id,
                ], [
                    'session_uuid' => \Str::uuid(),
                    'subject_id' => $defaultSubjectId, // Use first available subject
                    'session_date' => $sessionDate->format('Y-m-d'),
                    'start_time' => '10:00:00',
                    'end_time' => '11:00:00',
                    'status' => 'completed',
                    'actual_duration_minutes' => 60,
                    'completion_date' => $sessionDate,
                    'teacher_marked_present' => true,
                    'student_marked_present' => true,
                ]);

                TeacherReview::create([
                    'teacher_id' => $teacher->id,
                    'student_id' => $student->id,
                    'session_id' => $session->id,
                    'rating' => rand(4, 5), // Ratings between 4-5 stars
                    'review' => $reviewTexts[array_rand($reviewTexts)],
                    'created_at' => now()->subDays(rand(1, 60)),
                    'updated_at' => now()->subDays(rand(1, 60)),
                ]);
            }

            // Update teacher profile with aggregated rating and review count
            $teacherReviews = TeacherReview::where('teacher_id', $teacher->id)->get();
            if ($teacherReviews->count() > 0) {
                $averageRating = round($teacherReviews->avg('rating'), 1);
                $reviewsCount = $teacherReviews->count();

                $teacher->teacherProfile->update([
                    'rating' => $averageRating,
                    'reviews_count' => $reviewsCount,
                ]);
            }
        }
    }
}