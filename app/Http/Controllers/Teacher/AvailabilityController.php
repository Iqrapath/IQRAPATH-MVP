<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherAvailability;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends Controller
{
    use AuthorizesRequests;
    
    /**
     * Store a new availability.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TeacherAvailability::class);
        
        $user = Auth::user();
        
        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);
        
        // Create the availability
        TeacherAvailability::create([
            'teacher_id' => $user->id,
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'is_active' => true,
        ]);
        
        return back()->with('success', 'Availability added successfully.');
    }
    
    /**
     * Update an existing availability.
     */
    public function update(Request $request, TeacherAvailability $availability): RedirectResponse
    {
        $this->authorize('update', $availability);
        
        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_active' => 'boolean',
        ]);
        
        $availability->update($validated);
        
        return back()->with('success', 'Availability updated successfully.');
    }
    
    /**
     * Delete an availability.
     */
    public function destroy(TeacherAvailability $availability): RedirectResponse
    {
        $this->authorize('delete', $availability);
        
        $availability->delete();
        
        return back()->with('success', 'Availability deleted successfully.');
    }
    
    /**
     * Update time zone and preferred hours.
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $this->authorize('create', TeacherAvailability::class);
        
        $user = Auth::user();
        
        $validated = $request->validate([
            'time_zone' => 'required|string|max:50',
            'preferred_teaching_hours' => 'required|string|max:50',
            'availability_type' => 'required|in:Part-Time,Full-Time',
        ]);
        
        // Update all availabilities with the new preferences
        TeacherAvailability::where('teacher_id', $user->id)->update([
            'time_zone' => $validated['time_zone'],
            'preferred_teaching_hours' => $validated['preferred_teaching_hours'],
            'availability_type' => $validated['availability_type'],
        ]);
        
        // If no availabilities exist, create a default one
        if (TeacherAvailability::where('teacher_id', $user->id)->count() === 0) {
            TeacherAvailability::create([
                'teacher_id' => $user->id,
                'day_of_week' => 1, // Monday
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_active' => true,
                'time_zone' => $validated['time_zone'],
                'preferred_teaching_hours' => $validated['preferred_teaching_hours'],
                'availability_type' => $validated['availability_type'],
            ]);
        }
        
        return back()->with('success', 'Availability preferences updated successfully.');
    }
} 