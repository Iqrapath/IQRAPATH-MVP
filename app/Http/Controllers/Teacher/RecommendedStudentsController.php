<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\Subject;
use App\Services\TeacherStatsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RecommendedStudentsController extends Controller
{
    public function __construct(
        private TeacherStatsService $statsService
    ) {}

    /**
     * Get recommended students for a teacher based on various algorithms.
     */
    public function getRecommendedStudents(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            $teacherId = $teacher->id;
            $teacherProfile = $teacher->teacherProfile;
            
            if (!$teacherProfile) {
                return response()->json(['students' => []]);
            }

            // Get teacher's subjects and specializations
            $teacherSubjects = $this->getTeacherSubjects($teacherId);
            $teacherSpecializations = $this->getTeacherSpecializations($teacherId);
            
            // Get all pending session requests
            $pendingRequests = $this->getPendingSessionRequests();
            
            // Apply recommendation algorithms
            $recommendations = $this->calculateRecommendations(
                $pendingRequests,
                $teacherSubjects,
                $teacherSpecializations,
                $teacherProfile
            );
            
            // Format recommendations for frontend
            $formattedRecommendations = $this->formatRecommendations($recommendations);
            
            return response()->json([
                'students' => $formattedRecommendations
            ]);
        } catch (\Exception $e) {
            \Log::error('RecommendedStudentsController error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'students' => [],
                'error' => 'Failed to fetch recommendations'
            ], 500);
        }
    }

    /**
     * Get teacher's teaching subjects.
     */
    private function getTeacherSubjects(int $teacherId): array
    {
        try {
            // Get teacher profile ID first
            $teacherProfile = DB::table('teacher_profiles')
                ->where('user_id', $teacherId)
                ->first();
                
            if (!$teacherProfile) {
                return [];
            }
            
            return DB::table('subjects')
                ->where('teacher_profile_id', $teacherProfile->id)
                ->join('subject_templates', 'subjects.subject_template_id', '=', 'subject_templates.id')
                ->where('subjects.is_active', true)
                ->pluck('subject_templates.name')
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('getTeacherSubjects error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get teacher's specializations.
     */
    private function getTeacherSpecializations(int $teacherId): array
    {
        // For now, return empty array since specializations field doesn't exist
        // This can be enhanced later when specializations are added to the schema
        return [];
    }

    /**
     * Get all pending session requests from students.
     */
    private function getPendingSessionRequests(): \Illuminate\Support\Collection
    {
        try {
            return Booking::with(['student.studentProfile', 'subject.template'])
                ->where('status', 'pending')
                ->where('booking_date', '>=', now())
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            \Log::error('getPendingSessionRequests error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Calculate recommendations using multiple algorithms.
     */
    private function calculateRecommendations(
        $pendingRequests,
        array $teacherSubjects,
        array $teacherSpecializations,
        $teacherProfile
    ): array {
        $recommendations = [];
        
        foreach ($pendingRequests as $request) {
            $score = $this->calculateCompatibilityScore(
                $request,
                $teacherSubjects,
                $teacherSpecializations,
                $teacherProfile
            );
            
            if ($score['total'] >= 70) { // Minimum compatibility threshold
                $recommendations[] = [
                    'request' => $request,
                    'score' => $score,
                    'match_reasons' => $this->getMatchReasons($score)
                ];
            }
        }
        
        // Sort by compatibility score (highest first)
        usort($recommendations, function ($a, $b) {
            return $b['score']['total'] <=> $a['score']['total'];
        });
        
        return array_slice($recommendations, 0, 10); // Return top 10
    }

    /**
     * Calculate compatibility score using multiple factors.
     */
    private function calculateCompatibilityScore(
        $request,
        array $teacherSubjects,
        array $teacherSpecializations,
        $teacherProfile
    ): array {
        $scores = [
            'subject_match' => 0,
            'specialization_match' => 0,
            'time_preference' => 0,
            'experience_level' => 0,
            'availability' => 0,
            'total' => 0
        ];
        
        // 1. Subject Match (40% weight)
        $requestSubject = $request->subject->template->name ?? $request->subject->name ?? '';
        if (in_array($requestSubject, $teacherSubjects)) {
            $scores['subject_match'] = 40;
        } else {
            // Partial match for similar subjects
            $scores['subject_match'] = $this->calculateSubjectSimilarity($requestSubject, $teacherSubjects);
        }
        
        // 2. Specialization Match (25% weight)
        if (!empty($teacherSpecializations)) {
            $requestSpecialization = $this->extractSpecializationFromSubject($requestSubject);
            if (in_array($requestSpecialization, $teacherSpecializations)) {
                $scores['specialization_match'] = 25;
            }
        }
        
        // 3. Time Preference (15% weight)
        $scores['time_preference'] = $this->calculateTimePreference($request, $teacherProfile);
        
        // 4. Experience Level (10% weight)
        $scores['experience_level'] = $this->calculateExperienceLevelMatch($request, $teacherProfile);
        
        // 5. Availability (10% weight)
        $scores['availability'] = $this->calculateAvailabilityMatch($request, $teacherProfile);
        
        // Calculate total score
        $scores['total'] = array_sum($scores) - $scores['total']; // Exclude total from sum
        
        return $scores;
    }

    /**
     * Calculate subject similarity score.
     */
    private function calculateSubjectSimilarity(string $requestSubject, array $teacherSubjects): int
    {
        $similarity = 0;
        foreach ($teacherSubjects as $teacherSubject) {
            similar_text(strtolower($requestSubject), strtolower($teacherSubject), $percent);
            $similarity = max($similarity, $percent);
        }
        return (int) ($similarity * 0.4); // Convert to 40% scale
    }

    /**
     * Extract specialization from subject name.
     */
    private function extractSpecializationFromSubject(string $subject): string
    {
        $specializations = ['Hifz', 'Tajweed', 'Hadith', 'Fiqh', 'Arabic', 'Quran'];
        foreach ($specializations as $spec) {
            if (stripos($subject, $spec) !== false) {
                return $spec;
            }
        }
        return $subject;
    }

    /**
     * Calculate time preference match.
     */
    private function calculateTimePreference($request, $teacherProfile): int
    {
        try {
            // This would ideally check teacher's preferred teaching times
            // For now, return a base score
            $requestTime = $request->start_time;
            
            // Handle both string and Carbon instances
            if (is_string($requestTime)) {
                $hour = (int) date('H', strtotime($requestTime));
            } elseif ($requestTime instanceof \Carbon\Carbon) {
                $hour = (int) $requestTime->format('H');
            } else {
                return 10; // Base score if time is invalid
            }
            
            // Prefer morning (6-12) and evening (18-21) sessions
            if (($hour >= 6 && $hour < 12) || ($hour >= 18 && $hour < 21)) {
                return 15;
            }
            
            return 10; // Base score for other times
        } catch (\Exception $e) {
            \Log::error('calculateTimePreference error: ' . $e->getMessage());
            return 10; // Base score on error
        }
    }

    /**
     * Calculate experience level match.
     */
    private function calculateExperienceLevelMatch($request, $teacherProfile): int
    {
        // This would check if teacher's experience level matches student's needs
        // For now, return a base score
        return 10;
    }

    /**
     * Calculate availability match.
     */
    private function calculateAvailabilityMatch($request, $teacherProfile): int
    {
        // This would check teacher's actual availability
        // For now, return a base score
        return 10;
    }

    /**
     * Get match reasons based on scores.
     */
    private function getMatchReasons(array $scores): array
    {
        $reasons = [];
        
        if ($scores['subject_match'] >= 30) {
            $reasons[] = 'Subject match';
        }
        if ($scores['specialization_match'] >= 20) {
            $reasons[] = 'Specialization match';
        }
        if ($scores['time_preference'] >= 12) {
            $reasons[] = 'Time preference';
        }
        if ($scores['experience_level'] >= 8) {
            $reasons[] = 'Experience level';
        }
        if ($scores['availability'] >= 8) {
            $reasons[] = 'Availability';
        }
        
        return $reasons;
    }

    /**
     * Format recommendations for frontend.
     */
    private function formatRecommendations(array $recommendations): array
    {
        return array_map(function ($recommendation) {
            try {
                $request = $recommendation['request'];
                $student = $request->student;
                
                // Safely get subject name
                $subjectName = 'Unknown Subject';
                if ($request->subject) {
                    if ($request->subject->template) {
                        $subjectName = $request->subject->template->name;
                    } elseif ($request->subject->name) {
                        $subjectName = $request->subject->name;
                    }
                }
                
                return [
                    'id' => $request->id,
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->name,
                        'avatar' => $student->avatar,
                        'specialization' => $this->extractSpecializationFromSubject($subjectName),
                        'isOnline' => $this->isUserOnline($student->id),
                        'joinedDate' => $student->created_at ? $student->created_at->format('Y-m-d') : 'Unknown',
                        'location' => $this->getStudentLocation($student),
                        'age' => $this->getStudentAge($student),
                        'gender' => $this->getStudentGender($student),
                        'preferredLearningTime' => $this->getPreferredLearningTime($request),
                        'subjects' => [$subjectName],
                        'learningGoal' => $this->generateLearningGoal($request),
                        'availableDays' => $this->getAvailableDays($request),
                        'upcomingSessions' => $this->getUpcomingSessions($student)
                    ],
                    'request' => [
                        'description' => $this->generateRequestDescription($request),
                        'dateToStart' => $request->booking_date ? $request->booking_date->format('M j') : 'Unknown',
                        'time' => $this->formatTimeRange($request->start_time, $request->end_time),
                        'subjects' => [$subjectName],
                        'price' => '$' . $this->calculatePrice($request),
                        'priceNaira' => '₦' . number_format($this->calculatePrice($request) * 500)
                    ],
                    'compatibilityScore' => $recommendation['score']['total'],
                    'matchReasons' => $recommendation['match_reasons']
                ];
            } catch (\Exception $e) {
                \Log::error('formatRecommendations error: ' . $e->getMessage());
                return null;
            }
        }, array_filter($recommendations)); // Filter out null values
    }

    /**
     * Generate request description based on subject and student.
     */
    private function generateRequestDescription($request): string
    {
        $subject = $request->subject->template->name ?? $request->subject->name ?? 'Islamic Studies';
        $descriptions = [
            'Hifz' => 'Need help with Quran memorization and revision.',
            'Tajweed' => 'Looking for assistance with proper Quran recitation.',
            'Hadith' => 'Seeking guidance on Hadith studies and understanding.',
            'Fiqh' => 'Need help with Islamic jurisprudence and rulings.',
            'Arabic' => 'Looking for Arabic language learning support.',
            'Quran' => 'Seeking help with Quranic studies and understanding.'
        ];
        
        foreach ($descriptions as $key => $description) {
            if (stripos($subject, $key) !== false) {
                return $description;
            }
        }
        
        return 'Looking for assistance with Islamic studies.';
    }

    /**
     * Format time range for display.
     */
    private function formatTimeRange($startTime, $endTime): string
    {
        try {
            // Handle both string and Carbon instances
            if (is_string($startTime)) {
                $start = date('g:i A', strtotime($startTime));
                $hour = (int) date('H', strtotime($startTime));
            } elseif ($startTime instanceof \Carbon\Carbon) {
                $start = $startTime->format('g:i A');
                $hour = (int) $startTime->format('H');
            } else {
                return 'Time not specified';
            }
            
            if (is_string($endTime)) {
                $end = date('g:i A', strtotime($endTime));
            } elseif ($endTime instanceof \Carbon\Carbon) {
                $end = $endTime->format('g:i A');
            } else {
                $end = 'Unknown';
            }
            
            $period = '';
            if ($hour >= 6 && $hour < 12) {
                $period = 'Morning';
            } elseif ($hour >= 12 && $hour < 17) {
                $period = 'Afternoon';
            } elseif ($hour >= 17 && $hour < 21) {
                $period = 'Evening';
            } else {
                $period = 'Night';
            }
            
            return $period . ' (' . $start . ' - ' . $end . ')';
        } catch (\Exception $e) {
            \Log::error('formatTimeRange error: ' . $e->getMessage());
            return 'Time not specified';
        }
    }

    /**
     * Calculate price based on subject and complexity.
     */
    private function calculatePrice($request): int
    {
        $subject = $request->subject->template->name ?? $request->subject->name ?? '';
        $basePrice = 25;
        
        if (stripos($subject, 'Advanced') !== false || stripos($subject, 'Hadith') !== false) {
            return $basePrice + 15;
        } elseif (stripos($subject, 'Intermediate') !== false || stripos($subject, 'Fiqh') !== false) {
            return $basePrice + 10;
        } elseif (stripos($subject, 'Hifz') !== false) {
            return $basePrice + 5;
        }
        
        return $basePrice;
    }

    /**
     * Check if user is online.
     */
    private function isUserOnline(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user || !$user->last_active_at) {
            return false;
        }
        
        return $user->last_active_at->isAfter(now()->subMinutes(5));
    }

    /**
     * Get student location from student profile.
     */
    private function getStudentLocation($student): ?string
    {
        $studentProfile = $student->studentProfile;
        if ($studentProfile && $studentProfile->location) {
            return $studentProfile->location;
        }
        return 'Nigeria'; // Default location
    }

    /**
     * Get student age from student profile.
     */
    private function getStudentAge($student): ?int
    {
        try {
            $studentProfile = $student->studentProfile;
            if ($studentProfile && $studentProfile->date_of_birth) {
                return (int) now()->diffInYears($studentProfile->date_of_birth);
            }
            return null;
        } catch (\Exception $e) {
            \Log::error('getStudentAge error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get student gender from student profile.
     */
    private function getStudentGender($student): ?string
    {
        $studentProfile = $student->studentProfile;
        if ($studentProfile && $studentProfile->gender) {
            return ucfirst($studentProfile->gender);
        }
        return null;
    }

    /**
     * Get preferred learning time from request.
     */
    private function getPreferredLearningTime($request): string
    {
        $hour = (int) $request->start_time->format('H');
        
        if ($hour >= 6 && $hour < 12) {
            return 'Morning (6 AM - 12 PM)';
        } elseif ($hour >= 12 && $hour < 17) {
            return 'Afternoon (12 PM - 5 PM)';
        } else {
            return 'Evening (5 PM - 9 PM)';
        }
    }

    /**
     * Generate learning goal based on subject.
     */
    private function generateLearningGoal($request): string
    {
        $subject = $request->subject->template->name ?? $request->subject->name ?? '';
        
        $goals = [
            'Hifz' => 'Complete Hifz in 1 Year',
            'Tajweed' => 'Master Quran Recitation',
            'Hadith' => 'Study Major Hadith Collections',
            'Fiqh' => 'Learn Islamic Jurisprudence',
            'Arabic' => 'Achieve Arabic Fluency',
            'Quran' => 'Complete Quran Study'
        ];
        
        foreach ($goals as $key => $goal) {
            if (stripos($subject, $key) !== false) {
                return $goal;
            }
        }
        
        return 'Complete Islamic Studies';
    }

    /**
     * Get available days from request.
     */
    private function getAvailableDays($request): array
    {
        $dayOfWeek = $request->booking_date->dayOfWeek;
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        return [$days[$dayOfWeek]];
    }

    /**
     * Get upcoming sessions for student.
     */
    private function getUpcomingSessions($student): array
    {
        // Get upcoming sessions for this student (both approved and pending)
        $sessions = Booking::with(['subject.template'])
            ->where('student_id', $student->id)
            ->whereIn('status', ['approved', 'pending'])
            ->where('booking_date', '>=', now())
            ->orderBy('booking_date', 'asc')
            ->limit(2)
            ->get();
        
        return $sessions->map(function ($session) {
            return [
                'time' => $session->start_time->format('g A'),
                'endTime' => $session->end_time->format('g A'),
                'day' => $session->booking_date->format('l'),
                'lesson' => $session->subject->template->name ?? $session->subject->name ?? 'Lesson',
                'status' => ucfirst($session->status)
            ];
        })->toArray();
    }
}
