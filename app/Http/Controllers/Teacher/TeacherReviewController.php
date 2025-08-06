<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherReview;
use App\Models\TeacherProfile;
use App\Models\TeachingSession;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class TeacherReviewController extends Controller
{
    /**
     * Display a listing of the reviews for a teacher.
     */
    public function index($teacherId)
    {
        $reviews = TeacherReview::with(['student', 'session'])
            ->where('teacher_id', $teacherId)
            ->latest()
            ->get();

        return response()->json($reviews);
    }

    /**
     * Store a newly created review in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['student', 'guardian'])) {
            abort(403, 'Only students or guardians can submit reviews.');
        }

        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'session_id' => 'required|exists:teaching_sessions,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:2000',
        ]);

        // Check if the student attended and completed the session
        $session = TeachingSession::where('id', $validated['session_id'])
            ->where('teacher_id', $validated['teacher_id'])
            ->first();
        if (!$session) {
            return back()->withErrors(['session' => 'Session not found.']);
        }

        // Policy authorization
        $this->authorize('create', [\App\Models\TeacherReview::class, $session]);

        // Ensure only one review per student/guardian per session
        $existing = TeacherReview::where('teacher_id', $validated['teacher_id'])
            ->where(function($q) use ($user) {
                if ($user->role === 'student') {
                    $q->where('student_id', $user->id);
                } else if ($user->role === 'guardian') {
                    $q->where('student_id', $session->student_id);
                }
            })
            ->where('session_id', $validated['session_id'])
            ->first();
        if ($existing) {
            return back()->withErrors(['review' => 'You have already reviewed this session.']);
        }

        // Store the review
        \DB::transaction(function () use ($validated, $user, $session) {
            TeacherReview::create([
                'teacher_id' => $validated['teacher_id'],
                'student_id' => $session->student_id,
                'session_id' => $validated['session_id'],
                'rating' => $validated['rating'],
                'review' => $validated['review'],
            ]);

            // Update teacher profile rating and review count
            $profile = \App\Models\TeacherProfile::where('user_id', $validated['teacher_id'])->first();
            if ($profile) {
                $agg = \App\Models\TeacherReview::where('teacher_id', $validated['teacher_id'])
                    ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as count_reviews')
                    ->first();
                $profile->rating = $agg->avg_rating;
                $profile->reviews_count = $agg->count_reviews;
                $profile->save();
            }
        });

        return back()->with('success', 'Thank you for your review!');
    }
}
