<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Subject;
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
     * Store a newly created subject.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Subject::class);
        
        $user = Auth::user();
        $teacherProfile = $user->teacherProfile;
        
        if (!$teacherProfile) {
            return back()->withErrors(['error' => 'Teacher profile not found.']);
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
        
        $teacherProfile->subjects()->create([
            'name' => $validated['name'],
            'is_active' => true,
        ]);
        
        return back()->with('success', 'Subject added successfully.');
    }
    
    /**
     * Update subject status.
     */
    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $this->authorize('update', $subject);
        
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);
        
        $subject->update($validated);
        
        return back()->with('success', 'Subject updated successfully.');
    }
    
    /**
     * Remove the specified subject.
     */
    public function destroy(Subject $subject): RedirectResponse
    {
        $this->authorize('delete', $subject);
        
        $subject->delete();
        
        return back()->with('success', 'Subject deleted successfully.');
    }
} 