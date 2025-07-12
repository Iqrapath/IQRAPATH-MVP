<?php

namespace App\Policies;

use App\Models\Dispute;
use App\Models\EvidenceAttachment;
use App\Models\Feedback;
use App\Models\SupportTicket;
use App\Models\TicketResponse;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EvidenceAttachmentPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EvidenceAttachment $attachment): bool
    {
        $attachable = $attachment->attachable;
        
        // Check based on the type of attachable
        if ($attachable instanceof Feedback) {
            return $this->canViewFeedbackAttachment($user, $attachable);
        } elseif ($attachable instanceof SupportTicket) {
            return $this->canViewTicketAttachment($user, $attachable);
        } elseif ($attachable instanceof Dispute) {
            return $this->canViewDisputeAttachment($user, $attachable);
        } elseif ($attachable instanceof TicketResponse) {
            return $this->canViewResponseAttachment($user, $attachable);
        }
        
        return false;
    }

    /**
     * Determine whether the user can download the model.
     */
    public function download(User $user, EvidenceAttachment $attachment): bool
    {
        return $this->view($user, $attachment);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EvidenceAttachment $attachment): bool
    {
        // General rule: the uploader can delete their own attachments
        if ($user->id === $attachment->uploaded_by) {
            return true;
        }
        
        $attachable = $attachment->attachable;
        
        // Check based on the type of attachable
        if ($attachable instanceof Feedback) {
            return $this->canDeleteFeedbackAttachment($user, $attachable);
        } elseif ($attachable instanceof SupportTicket) {
            return $this->canDeleteTicketAttachment($user, $attachable);
        } elseif ($attachable instanceof Dispute) {
            return $this->canDeleteDisputeAttachment($user, $attachable);
        } elseif ($attachable instanceof TicketResponse) {
            return $this->canDeleteResponseAttachment($user, $attachable);
        }
        
        return false;
    }
    
    /**
     * Check if user can view feedback attachment.
     */
    private function canViewFeedbackAttachment(User $user, Feedback $feedback): bool
    {
        return $user->id === $feedback->user_id || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }
    
    /**
     * Check if user can view ticket attachment.
     */
    private function canViewTicketAttachment(User $user, SupportTicket $ticket): bool
    {
        return $user->id === $ticket->user_id || 
               $user->id === $ticket->assigned_to || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }
    
    /**
     * Check if user can view dispute attachment.
     */
    private function canViewDisputeAttachment(User $user, Dispute $dispute): bool
    {
        return $user->id === $dispute->filed_by || 
               $user->id === $dispute->against || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }
    
    /**
     * Check if user can view response attachment.
     */
    private function canViewResponseAttachment(User $user, TicketResponse $response): bool
    {
        $ticket = $response->ticket;
        
        return $user->id === $ticket->user_id || 
               $user->id === $ticket->assigned_to || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }
    
    /**
     * Check if user can delete feedback attachment.
     */
    private function canDeleteFeedbackAttachment(User $user, Feedback $feedback): bool
    {
        return $user->role === 'super-admin' || $user->role === 'admin';
    }
    
    /**
     * Check if user can delete ticket attachment.
     */
    private function canDeleteTicketAttachment(User $user, SupportTicket $ticket): bool
    {
        return $user->role === 'super-admin' || $user->role === 'admin';
    }
    
    /**
     * Check if user can delete dispute attachment.
     */
    private function canDeleteDisputeAttachment(User $user, Dispute $dispute): bool
    {
        return $user->role === 'super-admin' || $user->role === 'admin';
    }
    
    /**
     * Check if user can delete response attachment.
     */
    private function canDeleteResponseAttachment(User $user, TicketResponse $response): bool
    {
        return $user->id === $response->responder_id || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }
}
