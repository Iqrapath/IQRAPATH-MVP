<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use App\Models\Dispute;
use App\Models\EvidenceAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DisputeController extends Controller
{
    /**
     * Display a listing of the disputes.
     */
    public function index(Request $request)
    {
        $query = Dispute::with(['filer', 'respondent']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('complaint_id', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('issue', 'like', "%{$search}%")
                  ->orWhereHas('filer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('respondent', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // For regular users, only show disputes they're involved in
        if (Auth::user()->role !== 'admin') {
            $userId = Auth::id();
            $query->where(function($q) use ($userId) {
                $q->where('filed_by', $userId)
                  ->orWhere('against', $userId);
            });
        }

        $disputes = $query->latest()->paginate(10);

        return Inertia::render('Admin/Disputes/Index', [
            'disputes' => $disputes,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new dispute.
     */
    public function create()
    {
        // Get users that can be disputed against (teachers, students)
        $users = User::whereIn('role', ['teacher', 'student'])
            ->where('id', '!=', Auth::id())
            ->get(['id', 'name', 'email', 'role']);

        return Inertia::render('Disputes/Create', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created dispute in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'against' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'issue' => 'required|string',
        ]);

        $dispute = Dispute::create([
            'filed_by' => Auth::id(),
            'against' => $validated['against'],
            'subject' => $validated['subject'],
            'issue' => $validated['issue'],
            'status' => 'open',
        ]);

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('dispute_attachments', 'public');
                
                $dispute->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }

        // Log action
        ActionLog::create([
            'loggable_id' => $dispute->id,
            'loggable_type' => Dispute::class,
            'action' => 'created',
            'performed_by' => Auth::id(),
            'details' => ['complaint_id' => $dispute->complaint_id],
        ]);

        return redirect()->route('disputes.index')
            ->with('success', 'Dispute filed successfully.');
    }

    /**
     * Display the specified dispute.
     */
    public function show(Dispute $dispute)
    {
        // Check if user can view this dispute
        if (Auth::user()->role !== 'admin' && Auth::id() !== $dispute->filed_by && Auth::id() !== $dispute->against) {
            abort(403);
        }

        $dispute->load(['filer', 'respondent', 'attachments.uploader', 'actionLogs.performer']);

        return Inertia::render('Admin/Disputes/Show', [
            'dispute' => $dispute,
        ]);
    }

    /**
     * Update the specified dispute in storage.
     */
    public function update(Request $request, Dispute $dispute)
    {
        // Only admins can update dispute status
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:open,under_review,resolved,dismissed',
        ]);

        $oldStatus = $dispute->status;
        $dispute->update($validated);

        // Set resolved_at timestamp if status is changed to resolved
        if ($validated['status'] === 'resolved' && $oldStatus !== 'resolved') {
            $dispute->resolved_at = now();
            $dispute->save();
        }

        // Log action
        ActionLog::create([
            'loggable_id' => $dispute->id,
            'loggable_type' => Dispute::class,
            'action' => 'status_updated',
            'performed_by' => Auth::id(),
            'details' => [
                'old_status' => $oldStatus,
                'new_status' => $dispute->status,
            ],
        ]);

        return back()->with('success', 'Dispute status updated successfully.');
    }

    /**
     * Upload attachments to the dispute.
     */
    public function uploadAttachment(Request $request, Dispute $dispute)
    {
        // Check if user can add attachments
        if (Auth::user()->role !== 'admin' && Auth::id() !== $dispute->filed_by && Auth::id() !== $dispute->against) {
            abort(403);
        }

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $path = $file->store('dispute_attachments', 'public');
        
        $attachment = $dispute->attachments()->create([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'description' => $request->description,
            'uploaded_by' => Auth::id(),
        ]);

        // Log action
        ActionLog::create([
            'loggable_id' => $dispute->id,
            'loggable_type' => Dispute::class,
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
        // Check if the attachment belongs to a dispute
        if ($attachment->attachable_type !== Dispute::class) {
            abort(404);
        }

        $dispute = $attachment->attachable;

        // Check if user can download this attachment
        if (Auth::user()->role !== 'admin' && Auth::id() !== $dispute->filed_by && Auth::id() !== $dispute->against) {
            abort(403);
        }

        // Use response()->download() instead of Storage::download()
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
        // Check if the attachment belongs to a dispute
        if ($attachment->attachable_type !== Dispute::class) {
            abort(404);
        }

        $dispute = $attachment->attachable;
        
        // Check if user can delete this attachment
        if (Auth::user()->role !== 'admin' && Auth::id() !== $attachment->uploaded_by) {
            abort(403);
        }
        
        // Delete the file
        Storage::disk('public')->delete($attachment->file_path);
        
        // Delete the attachment record
        $attachment->delete();

        // Log action
        ActionLog::create([
            'loggable_id' => $dispute->id,
            'loggable_type' => Dispute::class,
            'action' => 'attachment_deleted',
            'performed_by' => Auth::id(),
            'details' => ['attachment_name' => $attachment->file_name],
        ]);

        return back()->with('success', 'Attachment deleted successfully.');
    }

    /**
     * Contact both parties involved in the dispute.
     */
    public function contactParties(Request $request, Dispute $dispute)
    {
        // Only admins can contact both parties
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        // TODO: Implement notification sending to both parties
        // This would involve sending emails or in-app notifications

        // Log action
        ActionLog::create([
            'loggable_id' => $dispute->id,
            'loggable_type' => Dispute::class,
            'action' => 'parties_contacted',
            'performed_by' => Auth::id(),
            'details' => ['message_length' => strlen($validated['message'])],
        ]);

        return back()->with('success', 'Message sent to both parties successfully.');
    }
}
