<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Models\SubjectTemplates;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the guardian dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $guardianProfile = $user->guardianProfile;
        
        // Get children profiles (student profiles managed by this guardian)
        $children = StudentProfile::where('guardian_id', $user->id)
                                 ->whereHas('user', function($query) use ($user) {
                                     $query->where('email', 'like', '%child.of.' . str_replace('@', '.', $user->email));
                                 })
                                 ->with(['user', 'user.studentLearningSchedules'])
                                 ->get()
                                 ->map(function ($child) {
                                     // Extract preferred learning times from schedules
                                     $preferredTimes = [
                                         'monday' => ['enabled' => false, 'from' => '', 'to' => ''],
                                         'tuesday' => ['enabled' => false, 'from' => '', 'to' => ''],
                                         'wednesday' => ['enabled' => false, 'from' => '', 'to' => ''],
                                         'thursday' => ['enabled' => false, 'from' => '', 'to' => ''],
                                         'friday' => ['enabled' => false, 'from' => '', 'to' => ''],
                                         'saturday' => ['enabled' => false, 'from' => '', 'to' => ''],
                                         'sunday' => ['enabled' => false, 'from' => '', 'to' => ''],
                                     ];
                                     
                                     if ($child->user && $child->user->studentLearningSchedules) {
                                         $schedules = $child->user->studentLearningSchedules->where('is_active', true);
                                         
                                         // Map day numbers to day names
                                         $dayNames = [
                                             0 => 'sunday',
                                             1 => 'monday', 
                                             2 => 'tuesday',
                                             3 => 'wednesday',
                                             4 => 'thursday',
                                             5 => 'friday',
                                             6 => 'saturday',
                                         ];
                                         
                                         foreach ($schedules as $schedule) {
                                             $dayName = $dayNames[$schedule->day_of_week] ?? null;
                                             if ($dayName) {
                                                 $preferredTimes[$dayName] = [
                                                     'enabled' => true,
                                                     'from' => substr($schedule->start_time, 0, 5), // Remove seconds
                                                     'to' => substr($schedule->end_time, 0, 5),     // Remove seconds
                                                 ];
                                             }
                                         }
                                     }
                                     
                                     return [
                                         'id' => $child->id,
                                         'name' => $child->user->name,
                                         'age' => $child->age_group,
                                         'gender' => $child->gender,
                                         'preferred_subjects' => $child->subjects_of_interest ?? [],
                                         'preferred_learning_times' => $preferredTimes,
                                     ];
                                 });
        
        return Inertia::render('guardian/dashboard', [
            'guardianProfile' => $guardianProfile,
            'children' => $children,
            'students' => $guardianProfile?->students()->with('user')->get(), // Keep existing students
            'availableSubjects' => SubjectTemplates::where('is_active', true)
                                                  ->orderBy('name')
                                                  ->pluck('name')
                                                  ->toArray(),
            'showOnboarding' => $request->session()->get('showOnboarding', false),
        ]);
    }
}
