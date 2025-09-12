<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\TeacherStatsService;
use App\Services\BookingNotificationService;
use App\Services\TeachingSessionMeetingService;
use App\Models\Booking;
use App\Models\SubjectTemplates;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class RequestsController extends Controller
{
    public function __construct(
        private TeacherStatsService $teacherStatsService,
        private BookingNotificationService $bookingNotificationService,
        private TeachingSessionMeetingService $meetingService
    ) {}

    public function index(Request $request): Response
    {
        try {
            $teacherId = $request->user()->id;

            // Get all requests (pending, accepted, declined)
            $allRequests = $this->getAllRequests($teacherId);

            // Get all available subjects from database
            $subjects = SubjectTemplates::where('is_active', true)
                ->orderBy('name')
                ->pluck('name')
                ->toArray();

            // Get all available languages from teacher profiles
            $languages = $this->getAvailableLanguages();

            // Get time preferences from student profiles
            $timePreferences = $this->getTimePreferences();

            // Get budget ranges based on teacher hourly rates
            $budgetRanges = $this->getBudgetRanges();

            return Inertia::render('teacher/requests/index', [
                'requests' => $allRequests ?? [],
                'subjects' => $subjects ?? [],
                'languages' => $languages ?? [],
                'timePreferences' => $timePreferences ?? [],
                'budgetRanges' => $budgetRanges ?? [],
            ]);
        } catch (\Exception $e) {
            \Log::error('RequestsController index error: ' . $e->getMessage(), [
                'teacher_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('teacher/requests/index', [
                'requests' => [],
                'subjects' => [],
                'languages' => [],
                'timePreferences' => [],
                'budgetRanges' => [],
            ]);
        }
    }

    /**
     * Accept a booking request.
     */
    public function accept(Request $request, int $requestId): JsonResponse
    {
        try {
            $teacherId = $request->user()->id;
            
            $booking = Booking::where('id', $requestId)
                ->where('teacher_id', $teacherId)
                ->where('status', 'pending')
                ->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request not found or already processed.'
                ], 404);
            }

            // Update booking status to approved
            $booking->update(['status' => 'approved']);

            // Create teaching session
            $teachingSession = \App\Models\TeachingSession::create([
                'session_uuid' => 'S-' . date('ymd') . str_pad(random_int(1, 999), 3, '0', STR_PAD_LEFT),
                'booking_id' => $booking->id,
                'teacher_id' => $teacherId,
                'student_id' => $booking->student_id,
                'subject_id' => $booking->subject_id,
                'session_date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'status' => 'scheduled',
            ]);

            // Create meeting links
            $meetingData = $this->meetingService->createMeetingLinks($teachingSession, $request->user());
            $this->meetingService->updateSessionWithMeetingData($teachingSession, $meetingData);

            // Send notifications
            $this->bookingNotificationService->sendBookingApprovedNotifications($booking);

            return response()->json([
                'success' => true,
                'message' => 'Request accepted successfully!',
                'session' => $teachingSession
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to accept request', [
                'request_id' => $requestId,
                'teacher_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to accept request. Please try again.'
            ], 500);
        }
    }

    /**
     * Decline a booking request.
     */
    public function decline(Request $request, int $requestId): JsonResponse
    {
        try {
            $teacherId = $request->user()->id;
            
            $booking = Booking::where('id', $requestId)
                ->where('teacher_id', $teacherId)
                ->where('status', 'pending')
                ->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request not found or already processed.'
                ], 404);
            }

            // Update booking status to declined
            $booking->update(['status' => 'declined']);

            // Send notifications
            $this->bookingNotificationService->sendBookingRejectedNotifications($booking, 'Teacher declined the booking request');

            return response()->json([
                'success' => true,
                'message' => 'Request declined successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to decline request', [
                'request_id' => $requestId,
                'teacher_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to decline request. Please try again.'
            ], 500);
        }
    }

    /**
     * Get all requests for the teacher.
     */
    private function getAllRequests(int $teacherId): array
    {
        return Booking::with(['student', 'student.studentProfile', 'subject.template', 'teacher.teacherProfile'])
            ->where('teacher_id', $teacherId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($booking) {
                // Get subject name with proper fallback
                $subjectName = 'Unknown Subject';
                if ($booking->subject) {
                    if ($booking->subject->template) {
                        $subjectName = $booking->subject->template->name;
                    } elseif (isset($booking->subject->name)) {
                        $subjectName = $booking->subject->name;
                    }
                }

                // Calculate duration
                $duration = '1 Hour'; // Default
                if ($booking->start_time && $booking->end_time) {
                    $start = \Carbon\Carbon::parse($booking->start_time);
                    $end = \Carbon\Carbon::parse($booking->end_time);
                    $minutes = $start->diffInMinutes($end);
                    $hours = floor($minutes / 60);
                    $remainingMinutes = $minutes % 60;
                    
                    if ($hours > 0) {
                        $duration = $hours . ' Hour' . ($hours > 1 ? 's' : '');
                        if ($remainingMinutes > 0) {
                            $duration .= ' ' . $remainingMinutes . ' Min';
                        }
                    } else {
                        $duration = $minutes . ' Minutes';
                    }
                }

                // Determine priority based on how long the request has been pending
                $hoursPending = $booking->created_at->diffInHours(now());
                $priority = 'low';
                if ($hoursPending > 48) {
                    $priority = 'high';
                } elseif ($hoursPending > 24) {
                    $priority = 'medium';
                }

                // Map status
                $status = $booking->status;
                if ($status === 'approved') {
                    $status = 'accepted';
                }

                // Calculate price based on teacher's hourly rate and session duration
                $price = 0;
                $priceUSD = 0;
                if ($booking->teacher && $booking->teacher->teacherProfile) {
                    $hourlyRateNGN = $booking->teacher->teacherProfile->hourly_rate_ngn ?? 0;
                    $hourlyRateUSD = $booking->teacher->teacherProfile->hourly_rate_usd ?? 0;
                    $durationHours = $booking->duration_minutes / 60;
                    $price = $hourlyRateNGN * $durationHours;
                    $priceUSD = $hourlyRateUSD * $durationHours;
                }

                // Format requested days (convert from booking data)
                $requestedDays = $this->formatRequestedDays($booking->booking_date);
                
                // Format requested time
                $requestedTime = $booking->start_time->format('g:i A') . ' - ' . $booking->end_time->format('g:i A');
                
                // Create subjects array
                $subjects = [$subjectName];
                if ($booking->subject && $booking->subject->template) {
                    $subjects = [$booking->subject->template->name];
                }

                return [
                    'id' => $booking->id,
                    'student' => [
                        'id' => $booking->student->id,
                        'name' => $booking->student->name,
                        'avatar' => $booking->student->studentProfile->profile_picture ?? null,
                        'level' => $booking->student->studentProfile->grade_level ?? 'Beginner',
                    ],
                    'subject' => $subjectName,
                    'requestedDays' => $requestedDays,
                    'requestedTime' => $requestedTime,
                    'subjects' => $subjects,
                    'note' => $booking->notes ?? 'No additional notes provided.',
                    'status' => $status,
                    'price' => $price,
                    'priceUSD' => $priceUSD,
                ];
            })
            ->toArray();
    }

    /**
     * Get icon for subject.
     */
    private function getSubjectIcon(string $subjectName): string
    {
        $subjectIcons = [
            'Tajweed' => '📖',
            'Arabic Grammar' => '📚',
            'Quran Memorization' => '🕌',
            'Islamic History' => '🏛️',
            'Arabic Conversation' => '💬',
            'Quran Recitation' => '🎵',
            'Islamic Studies' => '📿',
            'Arabic Reading' => '📖',
            'Hadith Studies' => '📜',
            'Fiqh' => '⚖️',
        ];

        foreach ($subjectIcons as $subject => $icon) {
            if (stripos($subjectName, $subject) !== false) {
                return $icon;
            }
        }

        return '📚'; // Default icon
    }

    /**
     * Format requested days from booking date.
     */
    private function formatRequestedDays($bookingDate): string
    {
        $dayOfWeek = $bookingDate->format('l');
        
        // For now, return the day of the week
        // In a real implementation, this might be more complex based on recurring bookings
        return $dayOfWeek;
    }

    /**
     * Get all available languages from teacher profiles.
     */
    private function getAvailableLanguages(): array
    {
        try {
            $languages = DB::table('teacher_profiles')
                ->whereNotNull('languages')
                ->pluck('languages')
                ->flatMap(function ($json) {
                    $decoded = json_decode($json, true);
                    return is_array($decoded) ? $decoded : [];
                })
                ->unique()
                ->values()
                ->toArray();

            // If no languages found in database, return common languages
            if (empty($languages)) {
                return ['English', 'Arabic', 'Hausa', 'Yoruba', 'Igbo', 'French'];
            }

            return $languages;
        } catch (\Exception $e) {
            \Log::error('Failed to fetch languages: ' . $e->getMessage());
            return ['English', 'Arabic', 'Hausa', 'Yoruba', 'Igbo', 'French'];
        }
    }

    /**
     * Get time preferences from student profiles.
     */
    private function getTimePreferences(): array
    {
        try {
            $timePreferences = DB::table('student_profiles')
                ->whereNotNull('preferred_learning_times')
                ->pluck('preferred_learning_times')
                ->flatMap(function ($json) {
                    $decoded = json_decode($json, true);
                    return is_array($decoded) ? $decoded : [];
                })
                ->unique()
                ->values()
                ->toArray();

            // If no time preferences found, return common options
            if (empty($timePreferences)) {
                return ['Morning', 'Afternoon', 'Evening', 'Weekend', 'Flexible'];
            }

            return $timePreferences;
        } catch (\Exception $e) {
            \Log::error('Failed to fetch time preferences: ' . $e->getMessage());
            return ['Morning', 'Afternoon', 'Evening', 'Weekend', 'Flexible'];
        }
    }

    /**
     * Get budget ranges based on teacher hourly rates.
     */
    private function getBudgetRanges(): array
    {
        try {
            $rates = DB::table('teacher_profiles')
                ->whereNotNull('hourly_rate_ngn')
                ->where('hourly_rate_ngn', '>', 0)
                ->pluck('hourly_rate_ngn')
                ->toArray();

            if (empty($rates)) {
                // Default budget ranges if no rates found
                return [
                    ['label' => 'Under ₦5,000', 'value' => 5000],
                    ['label' => '₦5,000 - ₦10,000', 'value' => 10000],
                    ['label' => '₦10,000 - ₦20,000', 'value' => 20000],
                    ['label' => '₦20,000 - ₦50,000', 'value' => 50000],
                    ['label' => 'Above ₦50,000', 'value' => 100000],
                ];
            }

            $minRate = min($rates);
            $maxRate = max($rates);
            $avgRate = array_sum($rates) / count($rates);

            // Create dynamic budget ranges based on actual data
            $ranges = [];
            
            if ($minRate < 5000) {
                $ranges[] = ['label' => 'Under ₦5,000', 'value' => 5000];
            }
            
            if ($minRate < 10000 && $maxRate > 5000) {
                $ranges[] = ['label' => '₦5,000 - ₦10,000', 'value' => 10000];
            }
            
            if ($minRate < 20000 && $maxRate > 10000) {
                $ranges[] = ['label' => '₦10,000 - ₦20,000', 'value' => 20000];
            }
            
            if ($minRate < 50000 && $maxRate > 20000) {
                $ranges[] = ['label' => '₦20,000 - ₦50,000', 'value' => 50000];
            }
            
            if ($maxRate > 50000) {
                $ranges[] = ['label' => 'Above ₦50,000', 'value' => 100000];
            }

            // Ensure we have at least some ranges
            if (empty($ranges)) {
                $ranges = [
                    ['label' => 'Under ₦5,000', 'value' => 5000],
                    ['label' => '₦5,000 - ₦10,000', 'value' => 10000],
                    ['label' => '₦10,000 - ₦20,000', 'value' => 20000],
                ];
            }

            return $ranges;
        } catch (\Exception $e) {
            \Log::error('Failed to fetch budget ranges: ' . $e->getMessage());
            return [
                ['label' => 'Under ₦5,000', 'value' => 5000],
                ['label' => '₦5,000 - ₦10,000', 'value' => 10000],
                ['label' => '₦10,000 - ₦20,000', 'value' => 20000],
                ['label' => '₦20,000 - ₦50,000', 'value' => 50000],
                ['label' => 'Above ₦50,000', 'value' => 100000],
            ];
        }
    }
}
