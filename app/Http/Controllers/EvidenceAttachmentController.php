<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use App\Models\Dispute;
use App\Models\EvidenceAttachment;
use App\Models\Feedback;
use App\Models\SupportTicket;
use App\Models\TicketResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EvidenceAttachmentController extends Controller
{
    /**
     * Upload a new attachment.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'attachable_type' => 'required|string|in:feedback,ticket,dispute,response',
            'attachable_id' => 'required|integer',
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string',
        ]);

        // Get the attachable model
        $attachableModel = $this->getAttachableModel($request->attachable_type, $request->attachable_id);
        
        // Check permissions
        $this->checkUploadPermissions($attachableModel);
        
        $file = $request->file('file');
        $path = $file->store($request->attachable_type . '_attachments', 'public');
        
        $attachment = $attachableModel->attachments()->create([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'description' => $request->description,
            'uploaded_by' => Auth::id(),
        ]);

        // Log action
        $this->logAction($attachableModel, 'attachment_added', [
            'attachment_id' => $attachment->id,
            'attachment_name' => $attachment->file_name,
        ]);

        return back()->with('success', 'Attachment uploaded successfully.');
    }

    /**
     * Download an attachment.
     */
    public function download(EvidenceAttachment $attachment)
    {
        // Get the attachable model
        $attachableModel = $attachment->attachable;
        
        // Check permissions
        $this->checkDownloadPermissions($attachableModel, $attachment);
        
        // Use response()->download() instead of Storage::disk()->download()
        return response()->download(
            Storage::disk('public')->path($attachment->file_path),
            $attachment->file_name
        );
    }

    /**
     * Delete an attachment.
     */
    public function delete(EvidenceAttachment $attachment)
    {
        // Get the attachable model
        $attachableModel = $attachment->attachable;
        
        // Check permissions
        $this->checkDeletePermissions($attachableModel, $attachment);
        
        // Delete the file
        Storage::disk('public')->delete($attachment->file_path);
        
        // Delete the attachment record
        $attachment->delete();

        // Log action
        $this->logAction($attachableModel, 'attachment_deleted', [
            'attachment_name' => $attachment->file_name,
        ]);

        return back()->with('success', 'Attachment deleted successfully.');
    }

    /**
     * Get the attachable model based on type and ID.
     */
    private function getAttachableModel(string $type, int $id)
    {
        return match($type) {
            'feedback' => Feedback::findOrFail($id),
            'ticket' => SupportTicket::findOrFail($id),
            'dispute' => Dispute::findOrFail($id),
            'response' => TicketResponse::findOrFail($id),
            default => abort(400, 'Invalid attachable type'),
        };
    }

    /**
     * Check if the user has permission to upload attachments to the model.
     */
    private function checkUploadPermissions($attachableModel)
    {
        $user = Auth::user();
        
        if ($attachableModel instanceof Feedback) {
            // Only the feedback creator or admins can add attachments
            if ($user->id !== $attachableModel->user_id && $user->role !== 'admin') {
                abort(403);
            }
        } elseif ($attachableModel instanceof SupportTicket) {
            // Only the ticket creator, assigned staff, or admins can add attachments
            if ($user->id !== $attachableModel->user_id && 
                $user->id !== $attachableModel->assigned_to && 
                $user->role !== 'admin') {
                abort(403);
            }
        } elseif ($attachableModel instanceof Dispute) {
            // Only the parties involved or admins can add attachments
            if ($user->id !== $attachableModel->filed_by && 
                $user->id !== $attachableModel->against && 
                $user->role !== 'admin') {
                abort(403);
            }
        } elseif ($attachableModel instanceof TicketResponse) {
            // Only the response creator or admins can add attachments
            if ($user->id !== $attachableModel->responder_id && $user->role !== 'admin') {
                abort(403);
            }
        }
    }

    /**
     * Check if the user has permission to download attachments from the model.
     */
    private function checkDownloadPermissions($attachableModel, $attachment)
    {
        $user = Auth::user();
        
        if ($attachableModel instanceof Feedback) {
            // Anyone with access to the feedback can download attachments
            if ($user->id !== $attachableModel->user_id && $user->role !== 'admin') {
                abort(403);
            }
        } elseif ($attachableModel instanceof SupportTicket) {
            // Only the ticket creator, assigned staff, or admins can download attachments
            if ($user->id !== $attachableModel->user_id && 
                $user->id !== $attachableModel->assigned_to && 
                $user->role !== 'admin') {
                abort(403);
            }
        } elseif ($attachableModel instanceof Dispute) {
            // Only the parties involved or admins can download attachments
            if ($user->id !== $attachableModel->filed_by && 
                $user->id !== $attachableModel->against && 
                $user->role !== 'admin') {
                abort(403);
            }
        } elseif ($attachableModel instanceof TicketResponse) {
            $ticket = $attachableModel->ticket;
            // Only those with access to the ticket can download response attachments
            if ($user->id !== $ticket->user_id && 
                $user->id !== $ticket->assigned_to && 
                $user->role !== 'admin') {
                abort(403);
            }
        }
    }

    /**
     * Check if the user has permission to delete attachments from the model.
     */
    private function checkDeletePermissions($attachableModel, $attachment)
    {
        $user = Auth::user();
        
        // General rule: Only the uploader or admins can delete attachments
        if ($user->id !== $attachment->uploaded_by) {
            if ($attachableModel instanceof Feedback && $user->role !== 'admin') {
                abort(403);
            } elseif ($attachableModel instanceof SupportTicket && $user->role !== 'admin') {
                abort(403);
            } elseif ($attachableModel instanceof Dispute && $user->role !== 'admin') {
                abort(403);
            } elseif ($attachableModel instanceof TicketResponse && $user->role !== 'admin') {
                abort(403);
            }
        }
    }

    /**
     * Log an action for the attachable model.
     */
    private function logAction($attachableModel, string $action, array $details = [])
    {
        $loggableType = match(true) {
            $attachableModel instanceof Feedback => Feedback::class,
            $attachableModel instanceof SupportTicket => SupportTicket::class,
            $attachableModel instanceof Dispute => Dispute::class,
            $attachableModel instanceof TicketResponse => SupportTicket::class, // Log to the parent ticket
            default => abort(400, 'Invalid loggable type'),
        };

        $loggableId = $attachableModel instanceof TicketResponse 
            ? $attachableModel->ticket_id 
            : $attachableModel->id;

        ActionLog::create([
            'loggable_id' => $loggableId,
            'loggable_type' => $loggableType,
            'action' => $action,
            'performed_by' => Auth::id(),
            'details' => $details,
        ]);
    }
}
