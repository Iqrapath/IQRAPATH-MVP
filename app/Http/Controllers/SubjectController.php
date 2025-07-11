<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the subjects.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Determine if we're in admin or teacher context
        $isAdminContext = $request->routeIs('admin.*');
        
        if ($isAdminContext) {
            // Admin view - show all subjects with teacher info
            $this->authorize('viewAny', Subject::class);
            
            $subjects = Subject::with('teacherProfile.user')
                ->orderBy('name')
                ->paginate(10);
                
            return Inertia::render('Admin/Subjects/Index', [
                'subjects' => $subjects
            ]);
        } else {
            // Teacher view - show only their subjects
            $teacherProfile = $user->teacherProfile;
            
            if (!$teacherProfile) {
                abort(403, 'Only teachers can access subjects');
            }
            
            $subjects = $teacherProfile->subjects()->orderBy('name')->get();
            
            return Inertia::render('Teacher/Subjects/Index', [
                'subjects' => $subjects
            ]);
        }
    }

    /**
     * Show the form for creating a new subject.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Subject::class);
        
        // Determine if we're in admin or teacher context
        $isAdminContext = $request->routeIs('admin.*');
        
        if ($isAdminContext) {
            $teachers = User::where('role', 'teacher')
                ->with('teacherProfile')
                ->get();
                
            return Inertia::render('Admin/Subjects/Create', [
                'teachers' => $teachers
            ]);
        } else {
            return Inertia::render('Teacher/Subjects/Create');
        }
    }

    /**
     * Store a newly created subject in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Subject::class);
        
        // Determine if we're in admin or teacher context
        $isAdminContext = $request->routeIs('admin.*');
        
        if ($isAdminContext) {
            $validated = $request->validate([
                'teacher_profile_id' => 'required|exists:teacher_profiles,id',
                'name' => [
                    'required', 
                    'string', 
                    'max:255',
                    Rule::unique('subjects')->where(function ($query) use ($request) {
                        return $query->where('teacher_profile_id', $request->teacher_profile_id);
                    })
                ],
            ]);
            
            Subject::create($validated);
            
            return redirect()->route('admin.subjects.index')
                ->with('success', 'Subject created successfully');
        } else {
            $user = $request->user();
            $teacherProfile = $user->teacherProfile;
            
            if (!$teacherProfile) {
                abort(403, 'Only teachers can create subjects');
            }
            
            $validated = $request->validate([
                'name' => [
                    'required', 
                    'string', 
                    'max:255',
                    Rule::unique('subjects')->where(function ($query) use ($teacherProfile) {
                        return $query->where('teacher_profile_id', $teacherProfile->id);
                    })
                ],
            ]);
            
            $teacherProfile->subjects()->create($validated);
            
            return redirect()->route('teacher.subjects.index')
                ->with('success', 'Subject created successfully');
        }
    }

    /**
     * Display the specified subject.
     */
    public function show(Request $request, Subject $subject): Response
    {
        $this->authorize('view', $subject);
        
        // Determine if we're in admin or teacher context
        $isAdminContext = $request->routeIs('admin.*');
        
        if ($isAdminContext) {
            return Inertia::render('Admin/Subjects/Show', [
                'subject' => $subject->load('teacherProfile.user')
            ]);
        } else {
            return Inertia::render('Teacher/Subjects/Show', [
                'subject' => $subject
            ]);
        }
    }

    /**
     * Show the form for editing the specified subject.
     */
    public function edit(Request $request, Subject $subject): Response
    {
        $this->authorize('update', $subject);
        
        // Determine if we're in admin or teacher context
        $isAdminContext = $request->routeIs('admin.*');
        
        if ($isAdminContext) {
            $teachers = User::where('role', 'teacher')
                ->with('teacherProfile')
                ->get();
                
            return Inertia::render('Admin/Subjects/Edit', [
                'subject' => $subject,
                'teachers' => $teachers
            ]);
        } else {
            return Inertia::render('Teacher/Subjects/Edit', [
                'subject' => $subject
            ]);
        }
    }

    /**
     * Update the specified subject in storage.
     */
    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $this->authorize('update', $subject);
        
        // Determine if we're in admin or teacher context
        $isAdminContext = $request->routeIs('admin.*');
        
        if ($isAdminContext) {
            $validated = $request->validate([
                'teacher_profile_id' => 'required|exists:teacher_profiles,id',
                'name' => [
                    'required', 
                    'string', 
                    'max:255',
                    Rule::unique('subjects')->where(function ($query) use ($request) {
                        return $query->where('teacher_profile_id', $request->teacher_profile_id);
                    })->ignore($subject->id)
                ],
            ]);
            
            $subject->update($validated);
            
            return redirect()->route('admin.subjects.index')
                ->with('success', 'Subject updated successfully');
        } else {
            $validated = $request->validate([
                'name' => [
                    'required', 
                    'string', 
                    'max:255',
                    Rule::unique('subjects')->where(function ($query) use ($subject) {
                        return $query->where('teacher_profile_id', $subject->teacher_profile_id);
                    })->ignore($subject->id)
                ],
            ]);
            
            $subject->update($validated);
            
            return redirect()->route('teacher.subjects.index')
                ->with('success', 'Subject updated successfully');
        }
    }

    /**
     * Remove the specified subject from storage.
     */
    public function destroy(Request $request, Subject $subject): RedirectResponse
    {
        $this->authorize('delete', $subject);
        
        $subject->delete();
        
        // Determine if we're in admin or teacher context
        $isAdminContext = $request->routeIs('admin.*');
        
        if ($isAdminContext) {
            return redirect()->route('admin.subjects.index')
                ->with('success', 'Subject deleted successfully');
        } else {
            return redirect()->route('teacher.subjects.index')
                ->with('success', 'Subject deleted successfully');
        }
    }
}
