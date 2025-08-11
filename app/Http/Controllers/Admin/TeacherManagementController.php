<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Subject;
use App\Models\TeacherAvailability;
use App\Models\TeacherEarning;
use App\Models\TeacherProfile;
use App\Models\TeachingSession;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TeacherManagementController extends Controller
{
    /**
     * Display a listing of teachers.
     */
    public function index(Request $request): Response
    {
        // Apply filters
        $query = User::where('role', 'teacher')
            ->with(['teacherProfile']);
            
        // Filter by status if provided
        if ($request->has('status') && $request->status && $request->status !== 'all') {
            $status = $request->status;
            $query->whereHas('teacherProfile', function ($q) use ($status) {
                if ($status === 'verified') {
                    $q->where('verified', true);
                } elseif ($status === 'pending') {
                    $q->where('verified', false)
                      ->whereHas('verificationRequests', function($vq) {
                          $vq->where('status', 'pending');
                      });
                } elseif ($status === 'inactive') {
                    $q->where('verified', false)
                      ->whereDoesntHave('verificationRequests', function($vq) {
                          $vq->where('status', 'pending');
                      });
                }
            });
        }
        
        // Filter by subject if provided
        if ($request->has('subject') && $request->subject && $request->subject !== 'all') {
            $subject = $request->subject;
            $query->whereHas('teacherProfile.subjects', function ($q) use ($subject) {
                $q->where('name', 'like', "%{$subject}%");
            });
        }
        
        // Filter by rating if provided
        if ($request->has('rating') && $request->rating && $request->rating !== 'all') {
            $rating = $request->rating;
            $query->whereHas('teacherProfile', function ($q) use ($rating) {
                $q->where('rating', '>=', $rating);
            });
        }
        
        // Search by name or email
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Get teachers with pagination
        $teachers = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(function ($teacher) {
                // Get classes held count
                $classesHeld = TeachingSession::where('teacher_id', $teacher->id)
                    ->where('status', 'completed')
                    ->count();
                    
                // Get subjects
                $subjects = $teacher->teacherProfile ? 
                    $teacher->teacherProfile->subjects->pluck('name')->join(', ') : '';
                    
                // Get verification status
                $status = 'Inactive';
                if ($teacher->teacherProfile) {
                    if ($teacher->teacherProfile->verified) {
                        $status = 'Approved';
                    } else {
                        $hasRequest = $teacher->teacherProfile->verificationRequests()
                            ->where('status', 'pending')
                            ->exists();
                        if ($hasRequest) {
                            $status = 'Pending';
                        }
                    }
                }
                
                // Get average rating from teacher profile
                $rating = null;
                if ($teacher->teacherProfile && $teacher->teacherProfile->rating !== null) {
                    $rating = is_numeric($teacher->teacherProfile->rating) ? (float) $teacher->teacherProfile->rating : null;
                }
                
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'email' => $teacher->email,
                    'avatar' => $teacher->avatar,
                    'subjects' => $subjects,
                    'rating' => $rating,
                    'classes_held' => $classesHeld,
                    'status' => $status,
                ];
            });
            
        // Get all subjects for filter dropdown
        $allSubjects = Subject::select('name')->distinct()->get()->pluck('name');
        
        // Get rating statistics
        $averageRating = TeacherProfile::avg('rating');
        
        $ratingStats = [
            'average' => $averageRating !== null ? (float) $averageRating : null,
            'total_teachers' => TeacherProfile::count(),
            'verified_teachers' => TeacherProfile::where('verified', true)->count(),
            'unverified_teachers' => TeacherProfile::where('verified', false)->count(),
        ];
        
        return Inertia::render('admin/teachers/index', [
            'teachers' => $teachers,
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? '',
                'subject' => $request->subject ?? '',
                'rating' => $request->rating ?? '',
            ],
            'subjects' => $allSubjects,
            'ratingStats' => $ratingStats,
        ]);
    }

    /**
     * Show the form for creating a new teacher.
     */
    public function create(): Response
    {
        return Inertia::render('admin/teachers/create');
    }

    /**
     * Store a newly created teacher in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'bio' => 'nullable|string|max:1000',
            'experience_years' => 'nullable|string|max:20',
            'languages' => 'nullable|array',
            'teaching_type' => 'nullable|string|max:50',
            'teaching_mode' => 'nullable|string|max:50',
        ]);

        // Start a database transaction
        DB::beginTransaction();

        try {
            // Create the user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'role' => 'teacher',
                'password' => bcrypt($validated['password']),
                'email_verified_at' => now(),
            ]);

            // Create the teacher profile
            $teacherProfile = TeacherProfile::create([
                'user_id' => $user->id,
                'bio' => $validated['bio'] ?? null,
                'experience_years' => $validated['experience_years'] ?? null,
                'verified' => false,
                'languages' => $validated['languages'] ?? null,
                'teaching_type' => $validated['teaching_type'] ?? null,
                'teaching_mode' => $validated['teaching_mode'] ?? null,
            ]);

            // Initialize teacher earnings record
            TeacherEarning::create([
                'teacher_id' => $user->id,
                'wallet_balance' => 0,
                'total_earned' => 0,
                'total_withdrawn' => 0,
                'pending_payouts' => 0,
            ]);

            DB::commit();

            return redirect()->route('admin.teachers.index')
                ->with('success', 'Teacher created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create teacher: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified teacher.
     */
    public function show(User $teacher): Response
    {
        // Ensure the user is a teacher
        if ($teacher->role !== 'teacher') {
            abort(404, 'Teacher not found');
        }

        // Load teacher profile with related data
        $teacher->load([
            'teacherProfile',
            'teacherProfile.subjects',
            'teacherProfile.documents',
            'availabilities',
            'earnings',
        ]);
        
        // Get teaching sessions stats
        $sessionsStats = [
            'total' => TeachingSession::where('teacher_id', $teacher->id)->count(),
            'completed' => TeachingSession::where('teacher_id', $teacher->id)
                ->where('status', 'completed')
                ->count(),
            'upcoming' => TeachingSession::where('teacher_id', $teacher->id)
                ->whereIn('status', ['scheduled'])
                ->where('session_date', '>=', now()->format('Y-m-d'))
                ->count(),
            'cancelled' => TeachingSession::where('teacher_id', $teacher->id)
                ->where('status', 'cancelled')
                ->count(),
        ];
        
        // Get upcoming sessions
        $upcomingSessions = TeachingSession::where('teacher_id', $teacher->id)
            ->with(['student', 'subject'])
            ->whereIn('status', ['scheduled'])
            ->where('session_date', '>=', now()->format('Y-m-d'))
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->take(5)
            ->get();
            
        // Format document data
        $documents = [
            'id_verifications' => $teacher->teacherProfile ? 
                $teacher->teacherProfile->idVerifications()->get() : [],
            'certificates' => $teacher->teacherProfile ? 
                $teacher->teacherProfile->certificates()->get() : [],
            'resume' => $teacher->teacherProfile ? 
                $teacher->teacherProfile->resume() : null,
        ];

        return Inertia::render('admin/teachers/show', [
            'teacher' => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'email' => $teacher->email,
                'phone' => $teacher->phone,
                'avatar' => $teacher->avatar,
                'location' => $teacher->location,
                'created_at' => $teacher->created_at,
                'status' => $teacher->status_type,
                'last_active' => $teacher->last_active_at,
            ],
            'profile' => $teacher->teacherProfile ? [
                'id' => $teacher->teacherProfile->id,
                'bio' => $teacher->teacherProfile->bio,
                'experience_years' => $teacher->teacherProfile->experience_years,
                'verified' => $teacher->teacherProfile->verified,
                'languages' => $teacher->teacherProfile->languages,
                'teaching_type' => $teacher->teacherProfile->teaching_type,
                'teaching_mode' => $teacher->teacherProfile->teaching_mode,
                'subjects' => $teacher->teacherProfile->subjects,
            ] : null,
            'earnings' => $teacher->earnings ? [
                'wallet_balance' => $teacher->earnings->wallet_balance,
                'total_earned' => $teacher->earnings->total_earned,
                'total_withdrawn' => $teacher->earnings->total_withdrawn,
                'pending_payouts' => $teacher->earnings->pending_payouts,
            ] : null,
            'availabilities' => $teacher->availabilities->map(function($availability) {
                return [
                    'id' => $availability->id,
                    'day_name' => $availability->day_name,
                    'time_range' => $availability->time_range,
                    'is_active' => $availability->is_active,
                ];
            }),
            'documents' => $documents,
            'sessions_stats' => $sessionsStats,
            'upcoming_sessions' => $upcomingSessions,
        ]);
    }

    /**
     * Show the form for editing the specified teacher.
     */
    public function edit(User $teacher): Response
    {
        // Ensure the user is a teacher
        if ($teacher->role !== 'teacher') {
            abort(404, 'Teacher not found');
        }

        // Load teacher profile
        $teacher->load(['teacherProfile']);

        return Inertia::render('admin/teachers/edit', [
            'teacher' => $teacher,
            'profile' => $teacher->teacherProfile,
        ]);
    }

    /**
     * Update the specified teacher in storage.
     */
    public function update(Request $request, User $teacher): RedirectResponse
    {
        // Ensure the user is a teacher
        if ($teacher->role !== 'teacher') {
            abort(404, 'Teacher not found');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $teacher->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'experience_years' => 'nullable|string|max:20',
            'languages' => 'nullable|array',
            'teaching_type' => 'nullable|string|max:50',
            'teaching_mode' => 'nullable|string|max:50',
        ]);

        // Start a database transaction
        DB::beginTransaction();

        try {
            // Update the user
            $teacher->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
            ]);

            // Update or create the teacher profile
            if ($teacher->teacherProfile) {
                $teacher->teacherProfile->update([
                    'bio' => $validated['bio'] ?? null,
                    'experience_years' => $validated['experience_years'] ?? null,
                    'languages' => $validated['languages'] ?? null,
                    'teaching_type' => $validated['teaching_type'] ?? null,
                    'teaching_mode' => $validated['teaching_mode'] ?? null,
                ]);
            } else {
                TeacherProfile::create([
                    'user_id' => $teacher->id,
                    'bio' => $validated['bio'] ?? null,
                    'experience_years' => $validated['experience_years'] ?? null,
                    'verified' => false,
                    'languages' => $validated['languages'] ?? null,
                    'teaching_type' => $validated['teaching_type'] ?? null,
                    'teaching_mode' => $validated['teaching_mode'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('admin.teachers.show', $teacher->id)
                ->with('success', 'Teacher updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update teacher: ' . $e->getMessage()]);
        }
    }

    /**
     * Approve a teacher.
     */
    public function approve(User $teacher): RedirectResponse
    {
        // Ensure the user is a teacher
        if ($teacher->role !== 'teacher') {
            abort(404, 'Teacher not found');
        }

        if (!$teacher->teacherProfile) {
            return back()->withErrors(['error' => 'Teacher profile not found.']);
        }

        // Get current admin user ID
        $adminId = request()->user()->id;

        // Update the teacher profile
        $teacher->teacherProfile->update([
            'verified' => true,
        ]);

        // Update any pending verification requests
        VerificationRequest::where('teacher_profile_id', $teacher->teacherProfile->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'verified',
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
            ]);

        return back()->with('success', 'Teacher approved successfully.');
    }

    /**
     * Reject a teacher.
     */
    public function reject(Request $request, User $teacher): RedirectResponse
    {
        // Ensure the user is a teacher
        if ($teacher->role !== 'teacher') {
            abort(404, 'Teacher not found');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        if (!$teacher->teacherProfile) {
            return back()->withErrors(['error' => 'Teacher profile not found.']);
        }

        // Get current admin user ID
        $adminId = $request->user()->id;

        // Update any pending verification requests
        VerificationRequest::where('teacher_profile_id', $teacher->teacherProfile->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);

        return back()->with('success', 'Teacher rejected successfully.');
    }

    /**
     * Download a teacher document.
     */
    public function downloadDocument(Document $document)
    {
        // Check if the document exists
        if (!Storage::exists($document->path)) {
            abort(404, 'Document not found');
        }

        return Storage::download($document->path, $document->name);
    }
} 