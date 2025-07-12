<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use App\Models\EvidenceAttachment;
use App\Models\SupportTicket;
use App\Models\TicketResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    /**
     * Display a listing of the support tickets.
     */
    public function index(Request $request)
    {
        $query = SupportTicket::with(['user', 'assignedStaff']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ticket_id', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('issue', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // For regular users, only show their own tickets
        if (Auth::user()->role !== 'super-admin') {
            $query->where('user_id', Auth::id());
        }

        $tickets = $query->latest()->paginate(10);

        // Get staff users for assignment dropdown
        $staffUsers = User::whereIn('role', ['super-admin', 'admin'])->get();

        return Inertia::render('Admin/Support/Index', [
            'tickets' => $tickets,
            'staffUsers' => $staffUsers,
            'filters' => $request->only(['status', 'assigned_to', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new support ticket.
     */
    public function create()
    {
        return Inertia::render('Support/Create');
    }

    /**
     * Store a newly created support ticket in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'issue' => 'required|string',
        ]);

        // Create ticket with user_id from authenticated user
        $validated['user_id'] = Auth::id();
        $ticket = SupportTicket::create($validated);

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket_attachments', 'public');
                
                $ticket->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }

        // Log action
        ActionLog::create([
            'loggable_id' => $ticket->id,
            'loggable_type' => SupportTicket::class,
            'action' => 'created',
            'performed_by' => Auth::id(),
            'details' => ['ticket_id' => $ticket->ticket_id],
        ]);

        return redirect()->route('support.index')
            ->with('success', 'Support ticket created successfully.');
    }

    /**
     * Display the specified support ticket.
     */
    public function show(SupportTicket $ticket)
    {
        // Check if user can view this ticket
        if (Auth::user()->role !== 'super-admin' && Auth::id() !== $ticket->user_id && Auth::id() !== $ticket->assigned_to) {
            abort(403);
        }

        $ticket->load([
            'user', 
            'assignedStaff', 
            'responses.responder', 
            'responses.attachments',
            'attachments.uploader', 
            'actionLogs.performer'
        ]);

        // Get staff users for assignment dropdown
        $staffUsers = User::whereIn('role', ['super-admin', 'admin'])->get();

        return Inertia::render('Admin/Support/Show', [
            'ticket' => $ticket,
            'staffUsers' => $staffUsers,
        ]);
    }

    /**
     * Update the specified support ticket in storage.
     */
    public function update(Request $request, SupportTicket $ticket)
    {
        // Check if user can update this ticket
        if (Auth::user()->role !== 'super-admin' && Auth::id() !== $ticket->assigned_to) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'sometimes|required|in:open,resolved,closed',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
        ]);

        $oldStatus = $ticket->status;
        $oldAssignedTo = $ticket->assigned_to;

        $ticket->update($validated);

        // Set resolved_at timestamp if status is changed to resolved
        if ($validated['status'] === 'resolved' && $oldStatus !== 'resolved') {
            $ticket->resolved_at = now();
            $ticket->save();
        }

        // Log status change
        if ($oldStatus !== $ticket->status) {
            ActionLog::create([
                'loggable_id' => $ticket->id,
                'loggable_type' => SupportTicket::class,
                'action' => 'status_updated',
                'performed_by' => Auth::id(),
                'details' => [
                    'old_status' => $oldStatus,
                    'new_status' => $ticket->status,
                ],
            ]);
        }

        // Log assignment change
        if ($oldAssignedTo !== $ticket->assigned_to) {
            ActionLog::create([
                'loggable_id' => $ticket->id,
                'loggable_type' => SupportTicket::class,
                'action' => 'assigned',
                'performed_by' => Auth::id(),
                'details' => [
                    'old_assigned_to' => $oldAssignedTo,
                    'new_assigned_to' => $ticket->assigned_to,
                ],
            ]);
        }

        return back()->with('success', 'Support ticket updated successfully.');
    }

    /**
     * Upload attachments to the support ticket.
     */
    public function uploadAttachment(Request $request, SupportTicket $ticket)
    {
        // Check if user can add attachments
        if (Auth::user()->role !== 'super-admin' && Auth::id() !== $ticket->user_id && Auth::id() !== $ticket->assigned_to) {
            abort(403);
        }

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $path = $file->store('ticket_attachments', 'public');
        
        $attachment = $ticket->attachments()->create([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'description' => $request->description,
            'uploaded_by' => Auth::id(),
        ]);

        // Log action
        ActionLog::create([
            'loggable_id' => $ticket->id,
            'loggable_type' => SupportTicket::class,
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
        // Check if the attachment belongs to a ticket
        if ($attachment->attachable_type !== SupportTicket::class) {
            abort(404);
        }

        $ticket = $attachment->attachable;

        // Check if user can download this attachment
        if (Auth::user()->role !== 'super-admin' && Auth::id() !== $ticket->user_id && Auth::id() !== $ticket->assigned_to) {
            abort(403);
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
        // Check if the attachment belongs to a ticket
        if ($attachment->attachable_type !== SupportTicket::class) {
            abort(404);
        }

        $ticket = $attachment->attachable;
        
        // Check if user can delete this attachment
        if (Auth::user()->role !== 'super-admin' && Auth::id() !== $attachment->uploaded_by) {
            abort(403);
        }
        
        // Delete the file
        Storage::disk('public')->delete($attachment->file_path);
        
        // Delete the attachment record
        $attachment->delete();

        // Log action
        ActionLog::create([
            'loggable_id' => $ticket->id,
            'loggable_type' => SupportTicket::class,
            'action' => 'attachment_deleted',
            'performed_by' => Auth::id(),
            'details' => ['attachment_name' => $attachment->file_name],
        ]);

        return back()->with('success', 'Attachment deleted successfully.');
    }
}
