<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TeacherProfile;
use App\Models\Subject;
use App\Models\SubjectTemplates;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class FindTeacherController extends Controller
{
    /**
     * Display the find teacher page with teacher data
     */
    public function index(Request $request): Response
    {
        $query = User::where('role', 'teacher')
            ->whereHas('teacherProfile', function ($q) {
                $q->where('verified', true);
            })
            ->with(['teacherProfile', 'teacherProfile.subjects.template', 'availabilities']); // Added 'availabilities'

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('teacherProfile.subjects.template', function ($subQuery) use ($search) {
                      $subQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply subject filter
        if ($request->filled('subject') && $request->subject !== 'All Subject') {
            $query->whereHas('teacherProfile.subjects.template', function ($q) use ($request) {
                $q->where('name', $request->subject);
            });
        }

        // Apply rating filter
        if ($request->filled('rating')) {
            $query->whereHas('teacherProfile', function ($q) use ($request) {
                $q->where('rating', '>=', $request->rating);
            });
        }

        // Apply time preference filter
        if ($request->filled('timePreference')) {
            $timePreference = $request->timePreference;
            if ($timePreference !== 'Any Time') {
                $query->whereHas('availabilities', function ($q) use ($timePreference) {
                    switch ($timePreference) {
                        case 'Morning (6 AM - 12 PM)':
                            $q->where('start_time', '>=', '06:00:00')
                              ->where('start_time', '<', '12:00:00');
                            break;
                        case 'Afternoon (12 PM - 6 PM)':
                            $q->where('start_time', '>=', '12:00:00')
                              ->where('start_time', '<', '18:00:00');
                            break;
                        case 'Evening (6 PM - 12 AM)':
                            $q->where('start_time', '>=', '18:00:00')
                              ->where('start_time', '<', '23:59:59');
                            break;
                        case 'Night (12 AM - 6 AM)':
                            $q->where(function ($subQ) {
                                $subQ->where('start_time', '>=', '00:00:00')
                                     ->where('start_time', '<', '06:00:00');
                            });
                            break;
                        case 'Weekdays Only':
                            $q->whereIn('day_of_week', [1, 2, 3, 4, 5]); // Monday to Friday
                            break;
                        case 'Weekends Only':
                            $q->whereIn('day_of_week', [0, 6]); // Sunday and Saturday
                            break;
                    }
                });
            }
        }

        // Apply language filter
        if ($request->filled('language') && $request->language !== 'Any Language') {
            $query->whereHas('teacherProfile', function ($q) use ($request) {
                $q->whereJsonContains('languages', $request->language);
            });
        }

        // Apply budget filter
        if ($request->filled('budget')) {
            $budget = (float) $request->budget;
            $query->whereHas('teacherProfile', function ($q) use ($budget) {
                $q->where('hourly_rate_usd', '<=', $budget);
            });
        }

        $teachers = $query->paginate(6);

        // Get all available subjects for filter (from templates to avoid duplicates)
        $subjects = SubjectTemplates::where('is_active', true)
            ->pluck('name')
            ->sort()
            ->values();

        // Get all available languages for filter
        $languages = TeacherProfile::where('verified', true)
            ->whereNotNull('languages')
            ->get()
            ->flatMap(function ($profile) {
                return $profile->languages ?? [];
            })
            ->unique()
            ->sort()
            ->values();

        // Get subject templates for the match teacher form
        $subjectTemplates = SubjectTemplates::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('find-teacher', [
            'teachers' => $teachers,
            'subjects' => $subjects,
            'languages' => $languages,
            'subjectTemplates' => $subjectTemplates,
            'filters' => [
                'search' => $request->search ?? '',
                'subject' => $request->subject ?? '',
                'rating' => $request->rating ?? '',
                'budget' => $request->budget ?? '',
                'timePreference' => $request->timePreference ?? '',
                'language' => $request->language ?? '',
            ],
        ]);
    }

    /**
     * Get teachers data for API
     */
    public function getTeachers(Request $request): JsonResponse
    {
        $query = User::where('role', 'teacher')
            ->whereHas('teacherProfile', function ($q) {
                $q->where('verified', true);
            })
            ->with(['teacherProfile', 'teacherProfile.subjects.template', 'availabilities']); // Added 'availabilities'

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('teacherProfile.subjects.template', function ($subQuery) use ($search) {
                      $subQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply subject filter
        if ($request->filled('subject') && $request->subject !== 'All Subject') {
            $query->whereHas('teacherProfile.subjects.template', function ($q) use ($request) {
                $q->where('name', $request->subject);
            });
        }

        // Apply rating filter
        if ($request->filled('rating')) {
            $query->whereHas('teacherProfile', function ($q) use ($request) {
                $q->where('rating', '>=', $request->rating);
            });
        }

        // Apply time preference filter
        if ($request->filled('timePreference')) {
            $timePreference = $request->timePreference;
            if ($timePreference !== 'Any Time') {
                $query->whereHas('availabilities', function ($q) use ($timePreference) {
                    switch ($timePreference) {
                        case 'Morning (6 AM - 12 PM)':
                            $q->where('start_time', '>=', '06:00:00')
                              ->where('start_time', '<', '12:00:00');
                            break;
                        case 'Afternoon (12 PM - 6 PM)':
                            $q->where('start_time', '>=', '12:00:00')
                              ->where('start_time', '<', '18:00:00');
                            break;
                        case 'Evening (6 PM - 12 AM)':
                            $q->where('start_time', '>=', '18:00:00')
                              ->where('start_time', '<', '23:59:59');
                            break;
                        case 'Night (12 AM - 6 AM)':
                            $q->where(function ($subQ) {
                                $subQ->where('start_time', '>=', '00:00:00')
                                     ->where('start_time', '<', '06:00:00');
                            });
                            break;
                        case 'Weekdays Only':
                            $q->whereIn('day_of_week', [1, 2, 3, 4, 5]); // Monday to Friday
                            break;
                        case 'Weekends Only':
                            $q->whereIn('day_of_week', [0, 6]); // Sunday and Saturday
                            break;
                    }
                });
            }
        }

        // Apply language filter
        if ($request->filled('language') && $request->language !== 'Any Language') {
            $query->whereHas('teacherProfile', function ($q) use ($request) {
                $q->whereJsonContains('languages', $request->language);
            });
        }

        // Apply budget filter
        if ($request->filled('budget')) {
            $budget = (float) $request->budget;
            $query->whereHas('teacherProfile', function ($q) use ($budget) {
                $q->where('hourly_rate_usd', '<=', $budget);
            });
        }

        // Paginate the results
        $perPage = 6; // Number of teachers per page
        $teachers = $query->paginate($perPage);

        $formattedTeachers = $teachers->getCollection()->map(function ($teacher) {
            return [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'image' => $teacher->avatar ? '/storage/' . $teacher->avatar : null,
                'intro_video_url' => $teacher->teacherProfile->intro_video_url ? '/storage/' . $teacher->teacherProfile->intro_video_url : null,
                'subjects' => $teacher->teacherProfile->subjects->pluck('template.name')->join(', '),
                'location' => $teacher->location ?? 'Location not specified',
                'rating' => $teacher->teacherProfile->rating ?? 0,
                'availability' => $this->formatAvailability($teacher),
                'price' => $teacher->teacherProfile->hourly_rate_usd ?? 0,
                'priceNaira' => number_format($teacher->teacherProfile->hourly_rate_ngn ?? 0, 0),
                'experience_years' => $teacher->teacherProfile->experience_years ?? 'Not specified',
                'reviews_count' => $teacher->teacherProfile->reviews_count ?? 0,
                'bio' => $teacher->teacherProfile->bio ?? '',
                'languages' => $teacher->teacherProfile->languages ?? [],
                'teaching_type' => $teacher->teacherProfile->teaching_type ?? 'Online',
                'teaching_mode' => $teacher->teacherProfile->teaching_mode ?? 'One-to-One',
            ];
        });

        return response()->json([
            'teachers' => $formattedTeachers,
            'pagination' => [
                'current_page' => $teachers->currentPage(),
                'last_page' => $teachers->lastPage(),
                'per_page' => $teachers->perPage(),
                'total' => $teachers->total(),
            ],
        ]);
    }

    /**
     * Format teacher availability for display
     */
    private function formatAvailability($teacher): array
    {
        if (!$teacher->availabilities || $teacher->availabilities->isEmpty()) {
            return ['No availability set'];
        }

        $availability = [];
        foreach ($teacher->availabilities as $slot) {
            $day = $this->getDayName($slot->day_of_week);
            $time = $slot->start_time . ' - ' . $slot->end_time;
            $availability[] = "$day: $time";
        }

        return $availability;
    }

    /**
     * Get day name from day number
     */
    private function getDayName($dayNumber): string
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return $days[$dayNumber] ?? 'Unknown';
    }

    /**
     * Process teacher matching request and return matched teachers
     */
    public function matchTeachers(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'student_age' => 'required|integer|min:5|max:100',
            'preferred_subject' => 'required|string',
            'best_time' => 'required|string',
        ]);

        // Build query for matching teachers
        $query = User::where('role', 'teacher')
            ->whereHas('teacherProfile', function ($q) {
                $q->where('verified', true);
            })
            ->with(['teacherProfile', 'teacherProfile.subjects.template', 'availabilities']);

        // Apply subject filter
        if ($request->preferred_subject && $request->preferred_subject !== 'Select Subject') {
            $query->whereHas('teacherProfile.subjects.template', function ($q) use ($request) {
                $q->where('name', $request->preferred_subject);
            });
        }

        // Apply time preference filter
        if ($request->best_time && $request->best_time !== 'Select preferred time') {
            $timePreference = $request->best_time;
            $query->whereHas('availabilities', function ($q) use ($timePreference) {
                switch ($timePreference) {
                    case 'morning':
                        $q->where('start_time', '>=', '06:00:00')
                          ->where('start_time', '<', '12:00:00');
                        break;
                    case 'afternoon':
                        $q->where('start_time', '>=', '12:00:00')
                          ->where('start_time', '<', '17:00:00');
                        break;
                    case 'evening':
                        $q->where('start_time', '>=', '17:00:00')
                          ->where('start_time', '<', '22:00:00');
                        break;
                }
            });
        }

        // Get matched teachers (limit to top 3 for best matches)
        $teachers = $query->get()
            ->sortByDesc(function ($teacher) {
                return $teacher->teacherProfile->rating ?? 0;
            })
            ->sortByDesc(function ($teacher) {
                return $teacher->teacherProfile->reviews_count ?? 0;
            })
            ->take(3);

        // Format matched teachers
        $matchedTeachers = $teachers->map(function ($teacher) {
            return [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'image' => $teacher->avatar ? '/storage/' . $teacher->avatar : null,
                'subjects' => $teacher->teacherProfile->subjects->pluck('template.name')->join(', '),
                'rating' => $teacher->teacherProfile->rating ?? 0,
                'reviews_count' => $teacher->teacherProfile->reviews_count ?? 0,
                'experience_years' => $teacher->teacherProfile->experience_years ?? 'Not specified',
                'price_naira' => $teacher->teacherProfile->hourly_rate_ngn ?? 0,
                'bio' => $teacher->teacherProfile->bio ?? '',
                'availability' => $this->formatAvailability($teacher),
            ];
        });

        return response()->json([
            'success' => true,
            'matched_teachers' => $matchedTeachers->toArray(),
            'total_matches' => $matchedTeachers->count(),
            'message' => $matchedTeachers->count() > 0 
                ? 'We found ' . $matchedTeachers->count() . ' teacher(s) that match your preferences!'
                : 'No teachers found matching your preferences. Please try different criteria.'
        ]);
    }
}