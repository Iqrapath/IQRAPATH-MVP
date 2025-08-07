<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherProfile;
use App\Models\Subject;
use App\Models\TeacherAvailability;
use App\Models\User;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\TeacherReview;
use App\Http\Controllers\DocumentController;
use App\Services\VideoCompressionService;

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
        
        // Fetch latest reviews for this teacher
        $reviews = TeacherReview::with(['student', 'session'])
            ->where('teacher_id', $user->id)
            ->latest()
            ->take(10)
            ->get();
            
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
        
        // Format available days for frontend
        $availableDays = [];
        for ($i = 1; $i <= 7; $i++) {
            $dayName = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][$i];
            $isSelected = $availabilities->where('day_of_week', $i)->count() > 0;
            $availableDays[] = [
                'id' => $i,
                'name' => $dayName,
                'is_selected' => $isSelected,
            ];
        }
        
        // Get teacher's subjects
        $teacherSubjects = $teacherProfile ? $teacherProfile->subjects()->where('is_active', true)->get() : collect();
        
        $subjectsWithSelection = $teacherSubjects->map(function ($subject) {
            return [
                'id' => $subject->id,
                'name' => $subject->name,
                'is_selected' => true,
            ];
        })->toArray();

        // Get documents by type with proper Document model integration
        $documents = [
            'certificates' => $teacherProfile ? DocumentController::formatDocumentsForDisplay(
                DocumentController::getDocumentsByType($teacherProfile, Document::TYPE_CERTIFICATE)
            ) : [],
        ];

        return Inertia::render('teacher/profile/index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'location' => $user->location,
                'created_at' => $user->created_at,
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
                'certifications' => $teacherProfile->certifications,

                'intro_video_url' => $teacherProfile->intro_video_url,
                'rating' => $teacherProfile->rating,
                'reviews_count' => $teacherProfile->reviews_count,
                'formatted_rating' => $teacherProfile->formattedRating,
                'join_date' => $teacherProfile->join_date,
            ] : null,
            'subjects' => $subjectsWithSelection,
            'documents' => $documents,
            'availabilities' => [
                'by_day' => $availabilitiesByDay,
                'time_zone' => $timeZone,
                'preferred_hours' => $preferredHours,
                'availability_type' => $availabilityType,
                'available_days' => $availableDays,
            ],
            'reviews' => $reviews,
        ]);
    }
    
    /**
     * Update basic profile information.
     */
    public function updateBasicInfo(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:20',
                'location' => 'nullable|string|max:255',
            ]);
            
            User::where('id', $user->id)->update($validated);
            
            // Refresh the user data in the session
            $user->refresh();
            
            return redirect()->route('teacher.profile.index')->with('success', 'Profile information updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'An unexpected error occurred. Please try again.'])->withInput();
        }
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
        
        try {
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
            
            return redirect()->route('teacher.profile.index')->with('success', 'Teacher profile updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'An unexpected error occurred. Please try again.'])->withInput();
        }
    }
    
    /**
     * Update or upload avatar.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $validated = $request->validate([
                'avatar' => 'required|image|max:2048',
            ]);
            
            // Delete old avatar if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            
            // Store new avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $avatarUrl = Storage::url($avatarPath);
            
            User::where('id', $user->id)->update(['avatar' => $avatarUrl]);
            
            return back()->with('success', 'Profile picture updated successfully.');
        } elseif ($request->input('avatar') === null) {
            // Remove avatar if it was set to null
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            
            User::where('id', $user->id)->update(['avatar' => null]);
            
            return back()->with('success', 'Profile picture removed successfully.');
        }
        
        return back()->withErrors(['avatar' => 'No avatar file provided.']);
    }
    
    /**
     * Update teaching subjects and expertise.
     */
    public function updateSubjects(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $profile = TeacherProfile::where('user_id', $user->id)->first();
        
        if (!$profile) {
            return back()->withErrors(['error' => 'Teacher profile not found.']);
        }
        
        try {
            $validated = $request->validate([
                'subjects' => 'required|array',
                'subjects.*' => 'string|max:100',
                'experience_years' => 'required|string|max:50',
            ]);
            
            // Update teacher profile
            TeacherProfile::where('id', $profile->id)->update([
                'experience_years' => $validated['experience_years'],
            ]);
            
            // Delete all existing subjects for this teacher
            Subject::where('teacher_profile_id', $profile->id)->delete();
            
            // Create new subjects for the teacher
            foreach ($validated['subjects'] as $subjectName) {
                if (!empty(trim($subjectName))) {
                    Subject::create([
                        'teacher_profile_id' => $profile->id,
                        'name' => trim($subjectName),
                        'is_active' => true,
                    ]);
                }
            }
            
            return redirect()->route('teacher.profile.index')->with('success', 'Teaching subjects updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('Teaching subjects update error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'profile_id' => $profile->id,
                'subjects' => $validated['subjects'] ?? [],
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'An unexpected error occurred. Please try again.'])->withInput();
        }
    }
    
    /**
     * Upload intro video.
     */
    public function uploadIntroVideo(Request $request)
    {
        $user = Auth::user();
        $profile = TeacherProfile::where('user_id', $user->id)->first();
        
        if (!$profile) {
            return back()->withErrors(['error' => 'Teacher profile not found.']);
        }
        
        try {
            // Validate the video file
            $validated = $request->validate([
                'video' => 'required|file|mimes:mp4,mov,avi|max:7168', // 7MB max
            ]);
            
            $videoFile = $request->file('video');
            
            // Validate video using our service
            $validationErrors = VideoCompressionService::validateVideo($videoFile);
            if (!empty($validationErrors)) {
                return back()->withErrors(['video' => $validationErrors])->withInput();
            }
            
            // Process video (no compression for now)
            $processedVideo = VideoCompressionService::processVideo($videoFile);
            
            // Delete old video if exists
            if ($profile->intro_video_url && Storage::disk('public')->exists($profile->intro_video_url)) {
                Storage::disk('public')->delete($profile->intro_video_url);
            }
            
            // Store the video
            $path = $processedVideo->store('videos/intros', 'public');
            TeacherProfile::where('id', $profile->id)->update(['intro_video_url' => $path]);
            
            // Log successful upload
            \Log::info('Intro video uploaded successfully', [
                'user_id' => $user->id,
                'profile_id' => $profile->id,
                'original_size' => $videoFile->getSize(),
                'stored_size' => Storage::disk('public')->size($path),
                'path' => $path
            ]);
            
            return redirect()->route('teacher.profile.index')->with('success', 'Intro video uploaded successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Illuminate\Http\Exceptions\PostTooLargeException $e) {
            \Log::error('Post too large exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['video' => 'Video file is too large. Please compress your video or choose a smaller file.'])->withInput();
        } catch (\Exception $e) {
            \Log::error('Intro video upload error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'profile_id' => $profile->id,
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'An unexpected error occurred. Please try again.'])->withInput();
        }
    }

    /**
     * Update availability and time zone preferences.
     */
    public function updateAvailability(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        try {
            $validated = $request->validate([
                'available_days' => 'required|array',
                'available_days.*' => 'integer|between:1,7',
                'preferred_teaching_hours' => 'required|string|max:100',
                'available_time' => 'required|in:Part-Time,Full-Time',
                'time_zone' => 'required|string|max:100',
            ]);
            
            // Delete existing availabilities for this teacher
            TeacherAvailability::where('teacher_id', $user->id)->delete();
            
            // Create new availabilities for selected days
            foreach ($validated['available_days'] as $dayId) {
                TeacherAvailability::create([
                    'teacher_id' => $user->id,
                    'day_of_week' => $dayId,
                    'start_time' => '09:00', // Default start time
                    'end_time' => '17:00', // Default end time
                    'is_active' => true,
                    'preferred_teaching_hours' => $validated['preferred_teaching_hours'],
                    'availability_type' => $validated['available_time'],
                    'time_zone' => $validated['time_zone'],
                ]);
            }
            
            return redirect()->route('teacher.profile.index')->with('success', 'Availability updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'An unexpected error occurred. Please try again.'])->withInput();
        }
    }
} 