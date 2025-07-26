<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherProfile;
use App\Models\Subject;
use App\Models\TeacherAvailability;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the teacher profile page.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        
        // Get teacher profile with related data
        $teacherProfile = TeacherProfile::where('user_id', $user->id)
            ->with(['subjects', 'documents'])
            ->first();
            
        // Get availabilities
        $availabilities = TeacherAvailability::where('teacher_id', $user->id)->get();
        
        // Get document counts by type
        $documentCounts = [
            'id_verifications' => $teacherProfile ? $teacherProfile->idVerifications()->count() : 0,
            'certificates' => $teacherProfile ? $teacherProfile->certificates()->count() : 0,
            'resume' => $teacherProfile && $teacherProfile->resume() ? 1 : 0,
        ];
        
        // Format availabilities by day
        $availabilitiesByDay = $availabilities->groupBy('day_of_week');
        
        // Get time zone and preferred hours
        $timeZone = $availabilities->first()?->time_zone ?? 'GMT+0';
        $preferredHours = $availabilities->first()?->preferred_teaching_hours ?? '';
        $availabilityType = $availabilities->first()?->availability_type ?? 'Part-Time';
        
        return Inertia::render('Teacher/Profile/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'location' => $user->location,
            ],
            'profile' => $teacherProfile ? [
                'id' => $teacherProfile->id,
                'bio' => $teacherProfile->bio,
                'experience_years' => $teacherProfile->experience_years,
                'verified' => $teacherProfile->verified,
                'languages' => $teacherProfile->languages,
                'teaching_type' => $teacherProfile->teaching_type,
                'teaching_mode' => $teacherProfile->teaching_mode,
                'education' => $teacherProfile->education,
                'qualification' => $teacherProfile->qualification,
                'intro_video_url' => $teacherProfile->intro_video_url,
                'rating' => $teacherProfile->rating,
                'reviews_count' => $teacherProfile->reviews_count,
                'formatted_rating' => $teacherProfile->formattedRating,
            ] : null,
            'subjects' => $teacherProfile ? $teacherProfile->subjects : [],
            'availabilities' => [
                'by_day' => $availabilitiesByDay,
                'time_zone' => $timeZone,
                'preferred_hours' => $preferredHours,
                'availability_type' => $availabilityType,
            ],
            'documents' => $documentCounts,
        ]);
    }
    
    /**
     * Update basic profile information.
     */
    public function updateBasicInfo(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
        ]);
        
        User::where('id', $user->id)->update($validated);
        
        return back()->with('success', 'Profile information updated successfully.');
    }
    
    /**
     * Update teacher profile information.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $profile = TeacherProfile::where('user_id', $user->id)->first();
        
        if (!$profile) {
            return back()->withErrors(['error' => 'Teacher profile not found.']);
        }
        
        $validated = $request->validate([
            'bio' => 'required|string|max:1000',
            'experience_years' => 'required|string|max:20',
            'languages' => 'required|array',
            'teaching_type' => 'required|string|max:50',
            'teaching_mode' => 'required|string|max:50',
            'education' => 'required|string|max:255',
            'qualification' => 'required|string|max:255',
        ]);
        
        TeacherProfile::where('id', $profile->id)->update($validated);
        
        return back()->with('success', 'Teacher profile updated successfully.');
    }
    
    /**
     * Update or upload avatar.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);
        
        // Delete old avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }
        
        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        User::where('id', $user->id)->update(['avatar' => $path]);
        
        return back()->with('success', 'Profile picture updated successfully.');
    }
    
    /**
     * Upload intro video.
     */
    public function uploadIntroVideo(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $profile = TeacherProfile::where('user_id', $user->id)->first();
        
        if (!$profile) {
            return back()->withErrors(['error' => 'Teacher profile not found.']);
        }
        
        $validated = $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi|max:51200', // 50MB max
        ]);
        
        // Delete old video if exists
        if ($profile->intro_video_url && Storage::disk('public')->exists($profile->intro_video_url)) {
            Storage::disk('public')->delete($profile->intro_video_url);
        }
        
        // Store new video
        $path = $request->file('video')->store('videos/intros', 'public');
        TeacherProfile::where('id', $profile->id)->update(['intro_video_url' => $path]);
        
        return back()->with('success', 'Intro video uploaded successfully.');
    }
} 