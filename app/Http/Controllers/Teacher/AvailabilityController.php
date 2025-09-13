<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class AvailabilityController extends Controller
{
    /**
     * Get teacher availability settings
     */
    public function getAvailability($teacherId)
    {
        try {
            // Debug authentication
            \Log::info('AvailabilityController: Auth check', [
                'auth_id' => Auth::id(),
                'teacher_id' => $teacherId,
                'is_authenticated' => Auth::check(),
                'user' => Auth::user() ? Auth::user()->toArray() : null,
                'session_id' => session()->getId()
            ]);

            if (!Auth::check()) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $teacher = Auth::user();
            
            // Verify the teacher is accessing their own data
            if ($teacher->id != $teacherId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Get availability from teacher_availabilities table
            $availability = DB::table('teacher_availabilities')
                ->where('teacher_id', $teacherId)
                ->first();
            
            if (!$availability) {
                return response()->json([
                    'holiday_mode' => false,
                    'available_days' => ['Mon', 'Wed', 'Thu'],
                    'day_schedules' => [
                        ['day' => 'Monday', 'enabled' => true, 'fromTime' => '', 'toTime' => ''],
                        ['day' => 'Tuesday', 'enabled' => false, 'fromTime' => '', 'toTime' => ''],
                        ['day' => 'Wednesday', 'enabled' => true, 'fromTime' => '', 'toTime' => ''],
                        ['day' => 'Thursday', 'enabled' => true, 'fromTime' => '', 'toTime' => ''],
                        ['day' => 'Friday', 'enabled' => false, 'fromTime' => '', 'toTime' => ''],
                        ['day' => 'Saturday', 'enabled' => false, 'fromTime' => '', 'toTime' => ''],
                        ['day' => 'Sunday', 'enabled' => false, 'fromTime' => '', 'toTime' => '']
                    ]
                ]);
            }

            // Parse availability data from JSON fields
            $holidayMode = $availability->holiday_mode ?? false;
            $availableDays = json_decode($availability->available_days ?? '["Mon", "Wed", "Thu"]', true);
            $daySchedules = json_decode($availability->day_schedules ?? '[]', true);
            
            // Ensure available_days is always an array
            if (!is_array($availableDays)) {
                $availableDays = ['Mon', 'Wed', 'Thu'];
            }
            
            // Ensure day_schedules is always an array
            if (!is_array($daySchedules)) {
                $daySchedules = [];
            }

            // If no day schedules exist in new format, check old format and convert
            if (empty($daySchedules)) {
                // Check if we have old format data
                $oldFormatRecords = DB::table('teacher_availabilities')
                    ->where('teacher_id', $teacherId)
                    ->whereNotNull('day_of_week')
                    ->whereNotNull('start_time')
                    ->whereNotNull('end_time')
                    ->get();

                if ($oldFormatRecords->isNotEmpty()) {
                    // Convert old format to new format
                    $daySchedules = $this->convertOldFormatToNew($oldFormatRecords);
                    $availableDays = $this->extractAvailableDays($oldFormatRecords);
                    
                    // Debug the conversion
                    \Log::info('AvailabilityController: Converted from old format', [
                        'old_records_count' => $oldFormatRecords->count(),
                        'converted_available_days' => $availableDays,
                        'converted_day_schedules' => $daySchedules
                    ]);
                } else {
                    // Create default empty schedules
                    $daySchedules = [
                        ['day' => 'Monday', 'enabled' => true, 'fromTime' => '', 'toTime' => ''],
                        ['day' => 'Tuesday', 'enabled' => false, 'fromTime' => '', 'toTime' => ''],
                        ['day' => 'Wednesday', 'enabled' => true, 'fromTime' => '', 'toTime' => ''],
                        ['day' => 'Thursday', 'enabled' => true, 'fromTime' => '', 'toTime' => ''],
                        ['day' => 'Friday', 'enabled' => false, 'fromTime' => '', 'toTime' => ''],
                        ['day' => 'Saturday', 'enabled' => false, 'fromTime' => '', 'toTime' => ''],
                        ['day' => 'Sunday', 'enabled' => false, 'fromTime' => '', 'toTime' => '']
                    ];
                }
            }

            // Debug logging
            \Log::info('AvailabilityController: Returning data', [
                'holiday_mode' => $holidayMode,
                'available_days' => $availableDays,
                'available_days_type' => gettype($availableDays),
                'day_schedules' => $daySchedules,
                'day_schedules_type' => gettype($daySchedules)
            ]);

            return response()->json([
                'holiday_mode' => $holidayMode,
                'available_days' => $availableDays,
                'day_schedules' => $daySchedules
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching teacher availability: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch availability settings'], 500);
        }
    }

    /**
     * Convert old format availability records to new format
     */
    private function convertOldFormatToNew($oldRecords)
    {
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $daySchedules = [];
        
        // Initialize all days as disabled with empty times
        foreach ($dayNames as $dayName) {
            $daySchedules[] = [
                'day' => $dayName,
                'enabled' => false,
                'fromTime' => '',
                'toTime' => ''
            ];
        }

        // Process old records and enable days with times
        $dayTimes = []; // Store all time ranges for each day
        
        foreach ($oldRecords as $record) {
            if ($record->is_active && $record->day_of_week !== null) {
                $dayIndex = $record->day_of_week;
                if (isset($daySchedules[$dayIndex])) {
                    $daySchedules[$dayIndex]['enabled'] = true;
                    
                    // Store time ranges for each day
                    if (!isset($dayTimes[$dayIndex])) {
                        $dayTimes[$dayIndex] = ['start' => [], 'end' => []];
                    }
                    
                    if ($record->start_time) {
                        $dayTimes[$dayIndex]['start'][] = $record->start_time;
                    }
                    if ($record->end_time) {
                        $dayTimes[$dayIndex]['end'][] = $record->end_time;
                    }
                }
            }
        }
        
        // Set the earliest start time and latest end time for each day
        foreach ($dayTimes as $dayIndex => $times) {
            if (!empty($times['start']) && !empty($times['end'])) {
                // Find earliest start time
                $earliestStart = min($times['start']);
                // Find latest end time
                $latestEnd = max($times['end']);
                
                $daySchedules[$dayIndex]['fromTime'] = $this->convertTimeFormat($earliestStart);
                $daySchedules[$dayIndex]['toTime'] = $this->convertTimeFormat($latestEnd);
            }
        }

        return $daySchedules;
    }

    /**
     * Extract available days from old format records
     */
    private function extractAvailableDays($oldRecords)
    {
        $dayAbbreviations = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $availableDays = [];

        // Get unique active days
        $activeDays = [];
        foreach ($oldRecords as $record) {
            if ($record->is_active && $record->day_of_week !== null) {
                $activeDays[] = $record->day_of_week;
            }
        }
        
        // Convert to day abbreviations and sort by day order
        $uniqueActiveDays = array_unique($activeDays);
        sort($uniqueActiveDays); // Sort by day order (0=Sunday, 1=Monday, etc.)
        
        foreach ($uniqueActiveDays as $dayIndex) {
            if (isset($dayAbbreviations[$dayIndex])) {
                $availableDays[] = $dayAbbreviations[$dayIndex];
            }
        }

        return $availableDays;
    }

    /**
     * Convert time from HH:MM:SS to H:MM AM/PM format
     */
    private function convertTimeFormat($time)
    {
        if (!$time) return '';
        
        $timeObj = \DateTime::createFromFormat('H:i:s', $time);
        if (!$timeObj) return '';
        
        return $timeObj->format('g:i A');
    }

    /**
     * Update teacher availability settings
     */
    public function updateAvailability(Request $request, $teacherId)
    {
        try {
            // Verify the teacher is accessing their own data
            if (Auth::id() != $teacherId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Validate the request
            $validator = Validator::make($request->all(), [
                'holiday_mode' => 'boolean',
                'available_days' => 'array',
                'day_schedules' => 'array'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 'Invalid data'], 400);
            }

            // Prepare data for database
            $holidayMode = $request->input('holiday_mode', false);
            $availableDays = json_encode($request->input('available_days', []));
            $daySchedules = json_encode($request->input('day_schedules', []));

            // Update or create availability record
            DB::table('teacher_availabilities')
                ->updateOrInsert(
                    ['teacher_id' => $teacherId],
                    [
                        'holiday_mode' => $holidayMode,
                        'available_days' => $availableDays,
                        'day_schedules' => $daySchedules,
                        'updated_at' => now()
                    ]
                );

            return response()->json(['message' => 'Availability updated successfully']);

        } catch (\Exception $e) {
            \Log::error('Error updating teacher availability: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update availability settings'], 500);
        }
    }
}