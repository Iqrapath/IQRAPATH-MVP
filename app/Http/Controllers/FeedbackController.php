<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use App\Models\EvidenceAttachment;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FeedbackController extends Controller
{
    /**
     * Display a listing of the feedback.
     */
    public function index(Request $request)
    {
        $query = Feedback::with('user');

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('feedback_type')) {
            $query->where('feedback_type', $request->feedback_type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $feedback = $query->latest()->paginate(10);

        return Inertia::render('Admin/Feedback/Index', [
            'feedback' => $feedback,
            'filters' => $request->only(['status', 'feedback_type', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new feedback.
     */
    public function create()
    {
        return Inertia::render('Feedback/Create');
    }

    /**
     * Store a newly created feedback in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'feedback_type' => 'required|string',
        ]);

        // Create feedback with user_id from authenticated user
        $validated['user_id'] = Auth::id();
        $feedback = Feedback::create($validated);

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('feedback_attachments', 'public');
                
                $feedback->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }

        // Log action
        ActionLog::create([
            'loggable_id' => $feedback->id,
            'loggable_type' => Feedback::class,
            'action' => 'created',
            'performed_by' => Auth::id(),
            'details' => ['feedback_id' => $feedback->id],
        ]);

        return redirect()->route('feedback.index')
            ->with('success', 'Feedback submitted successfully.');
    }

    /**
     * Display the specified feedback.
     */
    public function show(Feedback $feedback)
    {
        $feedback->load(['user', 'attachments.uploader', 'actionLogs.performer']);

        return Inertia::render('Admin/Feedback/Show', [
            'feedback' => $feedback,
        ]);
    }

    /**
     * Update the specified feedback in storage.
     */
    public function update(Request $request, Feedback $feedback)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,reviewed,archived',
        ]);

        $feedback->update($validated);

        // Log action
        ActionLog::create([
            'loggable_id' => $feedback->id,
            'loggable_type' => Feedback::class,
            'action' => 'status_updated',
            'performed_by' => Auth::id(),
            'details' => [
                'old_status' => $feedback->getOriginal('status'),
                'new_status' => $feedback->status,
            ],
        ]);

        return back()->with('success', 'Feedback status updated successfully.');
    }

    /**
     * Upload attachments to the feedback.
     */
    public function uploadAttachment(Request $request, Feedback $feedback)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $path = $file->store('feedback_attachments', 'public');
        
        $attachment = $feedback->attachments()->create([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'description' => $request->description,
            'uploaded_by' => Auth::id(),
        ]);

        // Log action
        ActionLog::create([
            'loggable_id' => $feedback->id,
            'loggable_type' => Feedback::class,
            'action' => 'attachment_added',
            'performed_by' => Auth::id(),
            'details' => ['attachment_id' => $attachment->id],
        ]);

        return back()->with('success', 'Attachment uploaded successfully.');
    }

    /**
     * Download an attachment.
     */
    public function downloadAttachment(EvidenceAttachment $attachment)
    {
        // Check if the attachment belongs to a feedback
        if ($attachment->attachable_type !== Feedback::class) {
            abort(404);
        }

        // Use response()->download() instead of Storage::disk()->download()
        return response()->download(
            Storage::disk('public')->path($attachment->file_path),
            $attachment->file_name
        );
    }

    /**
     * Delete an attachment.
     */
    public function deleteAttachment(EvidenceAttachment $attachment)
    {
        // Check if the attachment belongs to a feedback
        if ($attachment->attachable_type !== Feedback::class) {
            abort(404);
        }

        $feedback = $attachment->attachable;
        
        // Delete the file
        Storage::disk('public')->delete($attachment->file_path);
        
        // Delete the attachment record
        $attachment->delete();

        // Log action
        ActionLog::create([
            'loggable_id' => $feedback->id,
            'loggable_type' => Feedback::class,
            'action' => 'attachment_deleted',
            'performed_by' => Auth::id(),
            'details' => ['attachment_name' => $attachment->file_name],
        ]);

        return back()->with('success', 'Attachment deleted successfully.');
    }
}
