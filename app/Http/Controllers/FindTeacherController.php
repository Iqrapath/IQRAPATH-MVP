<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TeacherProfile;
use App\Models\Subject;
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
            ->with(['teacherProfile', 'teacherProfile.subjects', 'availabilities']); // Added 'availabilities'

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('teacherProfile.subjects', function ($subQuery) use ($search) {
                      $subQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply subject filter
        if ($request->filled('subject') && $request->subject !== 'All Subject') {
            $query->whereHas('teacherProfile.subjects', function ($q) use ($request) {
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

        // Get all available subjects for filter
        $subjects = Subject::where('is_active', true)
            ->distinct()
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

        return Inertia::render('find-teacher', [
            'teachers' => $teachers,
            'subjects' => $subjects,
            'languages' => $languages,
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
            ->with(['teacherProfile', 'teacherProfile.subjects', 'availabilities']); // Added 'availabilities'

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('teacherProfile.subjects', function ($subQuery) use ($search) {
                      $subQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply subject filter
        if ($request->filled('subject') && $request->subject !== 'All Subject') {
            $query->whereHas('teacherProfile.subjects', function ($q) use ($request) {
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
                'subjects' => $teacher->teacherProfile->subjects->pluck('name')->join(', '),
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
            'total' => $teachers->total(),
            'current_page' => $teachers->currentPage(),
            'last_page' => $teachers->lastPage(),
            'per_page' => $teachers->perPage(),
            'from' => $teachers->firstItem(),
            'to' => $teachers->lastItem(),
        ]);
    }

    /**
     * Format teacher availability
     */
    private function formatAvailability($teacher): string
    {
        // Check if teacher has availabilities
        if ($teacher->availabilities && $teacher->availabilities->count() > 0) {
            $availabilities = $teacher->availabilities->take(3); // Show first 3 availabilities
            $formatted = $availabilities->map(function ($availability) {
                $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                $dayName = $days[$availability->day_of_week] ?? 'Unknown';
                return $dayName . ' (' . substr($availability->start_time, 0, 5) . ' - ' . substr($availability->end_time, 0, 5) . ')';
            })->join(', ');
            
            if ($teacher->availabilities->count() > 3) {
                $formatted .= ' +' . ($teacher->availabilities->count() - 3) . ' more';
            }
            
            return $formatted;
        }
        
        // Fallback to default availability
        return 'Mon-Sat (9 AM - 6 PM)';
    }
}
