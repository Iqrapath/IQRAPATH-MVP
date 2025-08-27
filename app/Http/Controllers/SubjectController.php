<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\SubjectTemplates;
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
            
            $subjects = Subject::with(['teacherProfile.user', 'template'])
                ->orderBy('id')
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
            
            $subjects = $teacherProfile->subjects()->with('template')->orderBy('id')->get();
            
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
            
            // Get available subject templates
            $subjectTemplates = SubjectTemplates::where('is_active', true)
                ->orderBy('name')
                ->get();
                
            return Inertia::render('Admin/Subjects/Create', [
                'teachers' => $teachers,
                'subjectTemplates' => $subjectTemplates
            ]);
        } else {
            // Get available subject templates for teacher to choose from
            $subjectTemplates = SubjectTemplates::where('is_active', true)
                ->orderBy('name')
                ->get();
                
            return Inertia::render('Teacher/Subjects/Create', [
                'subjectTemplates' => $subjectTemplates
            ]);
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
                'subject_template_id' => 'required|exists:subject_templates,id',
                'teacher_notes' => 'nullable|string|max:1000',
            ]);
            
            // Check if teacher already has this subject
            $existingSubject = Subject::where('teacher_profile_id', $validated['teacher_profile_id'])
                ->where('subject_template_id', $validated['subject_template_id'])
                ->first();
                
            if ($existingSubject) {
                return back()->withErrors(['subject_template_id' => 'This teacher already teaches this subject.']);
            }
            
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
                'subject_template_id' => 'required|exists:subject_templates,id',
                'teacher_notes' => 'nullable|string|max:1000',
            ]);
            
            // Check if teacher already has this subject
            $existingSubject = Subject::where('teacher_profile_id', $teacherProfile->id)
                ->where('subject_template_id', $validated['subject_template_id'])
                ->first();
                
            if ($existingSubject) {
                return back()->withErrors(['subject_template_id' => 'You already teach this subject.']);
            }
            
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
                'subject' => $subject->load(['teacherProfile.user', 'template'])
            ]);
        } else {
            return Inertia::render('Teacher/Subjects/Show', [
                'subject' => $subject->load('template')
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
            
            // Get available subject templates
            $subjectTemplates = SubjectTemplates::where('is_active', true)
                ->orderBy('name')
                ->get();
                
            return Inertia::render('Admin/Subjects/Edit', [
                'subject' => $subject->load('template'),
                'teachers' => $teachers,
                'subjectTemplates' => $subjectTemplates
            ]);
        } else {
            // Get available subject templates for teacher to choose from
            $subjectTemplates = SubjectTemplates::where('is_active', true)
                ->orderBy('name')
                ->get();
                
            return Inertia::render('Teacher/Subjects/Edit', [
                'subject' => $subject->load('template'),
                'subjectTemplates' => $subjectTemplates
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
                'subject_template_id' => 'required|exists:subject_templates,id',
                'teacher_notes' => 'nullable|string|max:1000',
            ]);
            
            // Check if teacher already has this subject (excluding current subject)
            $existingSubject = Subject::where('teacher_profile_id', $validated['teacher_profile_id'])
                ->where('subject_template_id', $validated['subject_template_id'])
                ->where('id', '!=', $subject->id)
                ->first();
                
            if ($existingSubject) {
                return back()->withErrors(['subject_template_id' => 'This teacher already teaches this subject.']);
            }
            
            $subject->update($validated);
            
            return redirect()->route('admin.subjects.index')
                ->with('success', 'Subject updated successfully');
        } else {
            $validated = $request->validate([
                'subject_template_id' => 'required|exists:subject_templates,id',
                'teacher_notes' => 'nullable|string|max:1000',
            ]);
            
            // Check if teacher already has this subject (excluding current subject)
            $existingSubject = Subject::where('teacher_profile_id', $subject->teacher_profile_id)
                ->where('subject_template_id', $validated['subject_template_id'])
                ->where('id', '!=', $subject->id)
                ->first();
                
            if ($existingSubject) {
                return back()->withErrors(['subject_template_id' => 'You already teach this subject.']);
            }
            
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
