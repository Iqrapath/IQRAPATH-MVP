<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use App\Models\EvidenceAttachment;
use App\Models\SupportTicket;
use App\Models\TicketResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TicketResponseController extends Controller
{
    /**
     * Store a newly created response in storage.
     */
    public function store(Request $request, SupportTicket $ticket)
    {
        // Check if user can respond to this ticket
        if (Auth::id() !== $ticket->user_id && Auth::id() !== $ticket->assigned_to && Auth::user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'required|string',
            'notification_channels' => 'sometimes|array',
            'scheduled_for' => 'nullable|date|after:now',
        ]);

        $response = $ticket->responses()->create([
            'responder_id' => Auth::id(),
            'message' => $validated['message'],
            'notification_channels' => $request->notification_channels ?? ['in-app'],
            'scheduled_for' => $request->scheduled_for,
            'notification_sent' => false,
        ]);

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('response_attachments', 'public');
                
                $response->attachments()->create([
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
            'action' => 'response_added',
            'performed_by' => Auth::id(),
            'details' => [
                'response_id' => $response->id,
                'scheduled' => $request->scheduled_for ? true : false,
            ],
        ]);

        // If not scheduled, send notification now
        if (!$request->scheduled_for) {
            $this->sendNotification($response);
        }

        return back()->with('success', $request->scheduled_for 
            ? 'Response scheduled successfully.' 
            : 'Response sent successfully.');
    }

    /**
     * Update the specified response in storage.
     */
    public function update(Request $request, TicketResponse $response)
    {
        // Check if user can update this response
        if (Auth::id() !== $response->responder_id && Auth::user()->role !== 'admin') {
            abort(403);
        }

        // Only allow updating if notification hasn't been sent yet
        if ($response->notification_sent) {
            return back()->with('error', 'Cannot update a response that has already been sent.');
        }

        $validated = $request->validate([
            'message' => 'sometimes|required|string',
            'notification_channels' => 'sometimes|array',
            'scheduled_for' => 'nullable|date|after:now',
        ]);

        $response->update($validated);

        // Log action
        ActionLog::create([
            'loggable_id' => $response->ticket->id,
            'loggable_type' => SupportTicket::class,
            'action' => 'response_updated',
            'performed_by' => Auth::id(),
            'details' => ['response_id' => $response->id],
        ]);

        return back()->with('success', 'Response updated successfully.');
    }

    /**
     * Remove the specified response from storage.
     */
    public function destroy(TicketResponse $response)
    {
        // Check if user can delete this response
        if (Auth::id() !== $response->responder_id && Auth::user()->role !== 'admin') {
            abort(403);
        }

        // Only allow deleting if notification hasn't been sent yet
        if ($response->notification_sent) {
            return back()->with('error', 'Cannot delete a response that has already been sent.');
        }

        $ticketId = $response->ticket_id;

        // Delete attachments
        foreach ($response->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
            $attachment->delete();
        }

        $response->delete();

        // Log action
        ActionLog::create([
            'loggable_id' => $ticketId,
            'loggable_type' => SupportTicket::class,
            'action' => 'response_deleted',
            'performed_by' => Auth::id(),
            'details' => ['response_id' => $response->id],
        ]);

        return back()->with('success', 'Response deleted successfully.');
    }

    /**
     * Send a notification for the response.
     */
    public function sendNow(TicketResponse $response)
    {
        // Check if user can send this response
        if (Auth::id() !== $response->responder_id && Auth::user()->role !== 'admin') {
            abort(403);
        }

        // Only allow sending if notification hasn't been sent yet
        if ($response->notification_sent) {
            return back()->with('error', 'This response has already been sent.');
        }

        $this->sendNotification($response);

        return back()->with('success', 'Response sent successfully.');
    }

    /**
     * Upload attachments to the response.
     */
    public function uploadAttachment(Request $request, TicketResponse $response)
    {
        // Check if user can add attachments
        if (Auth::id() !== $response->responder_id && Auth::user()->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $path = $file->store('response_attachments', 'public');
        
        $attachment = $response->attachments()->create([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'description' => $request->description,
            'uploaded_by' => Auth::id(),
        ]);

        // Log action
        ActionLog::create([
            'loggable_id' => $response->ticket->id,
            'loggable_type' => SupportTicket::class,
            'action' => 'response_attachment_added',
            'performed_by' => Auth::id(),
            'details' => [
                'response_id' => $response->id,
                'attachment_id' => $attachment->id
            ],
        ]);

        return back()->with('success', 'Attachment uploaded successfully.');
    }

    /**
     * Download an attachment.
     */
    public function downloadAttachment(EvidenceAttachment $attachment)
    {
        // Check if the attachment belongs to a response
        if ($attachment->attachable_type !== TicketResponse::class) {
            abort(404);
        }

        $response = $attachment->attachable;
        $ticket = $response->ticket;

        // Check if user can download this attachment
        if (Auth::id() !== $ticket->user_id && Auth::id() !== $ticket->assigned_to && 
            Auth::id() !== $attachment->uploaded_by && Auth::user()->role !== 'admin') {
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
        // Check if the attachment belongs to a response
        if ($attachment->attachable_type !== TicketResponse::class) {
            abort(404);
        }

        $response = $attachment->attachable;
        
        // Check if user can delete this attachment
        if (Auth::id() !== $attachment->uploaded_by && Auth::user()->role !== 'admin') {
            abort(403);
        }
        
        // Delete the file
        Storage::disk('public')->delete($attachment->file_path);
        
        // Delete the attachment record
        $attachment->delete();

        // Log action
        ActionLog::create([
            'loggable_id' => $response->ticket->id,
            'loggable_type' => SupportTicket::class,
            'action' => 'response_attachment_deleted',
            'performed_by' => Auth::id(),
            'details' => [
                'response_id' => $response->id,
                'attachment_name' => $attachment->file_name
            ],
        ]);

        return back()->with('success', 'Attachment deleted successfully.');
    }

    /**
     * Send notification for a response.
     */
    private function sendNotification(TicketResponse $response)
    {
        // TODO: Implement actual notification sending logic
        // This would involve sending emails, SMS, or in-app notifications
        // based on the notification_channels field

        // For now, just mark as sent
        $response->update([
            'notification_sent' => true,
        ]);

        // Log action
        ActionLog::create([
            'loggable_id' => $response->ticket->id,
            'loggable_type' => SupportTicket::class,
            'action' => 'response_notification_sent',
            'performed_by' => Auth::id(),
            'details' => ['response_id' => $response->id],
        ]);
    }
}
