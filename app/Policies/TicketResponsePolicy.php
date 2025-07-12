<?php

namespace App\Policies;

use App\Models\TicketResponse;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TicketResponsePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TicketResponse $response): bool
    {
        $ticket = $response->ticket;
        
        // User can view responses to their own tickets, assigned staff can view responses to their assigned tickets,
        // and admins can view any response
        return $user->id === $ticket->user_id || 
               $user->id === $ticket->assigned_to || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, TicketResponse $response): bool
    {
        $ticket = $response->ticket;
        
        // User can respond to their own tickets, assigned staff can respond to their assigned tickets,
        // and admins can respond to any ticket
        return $user->id === $ticket->user_id || 
               $user->id === $ticket->assigned_to || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TicketResponse $response): bool
    {
        // Only the responder or admins can update responses, and only if not sent yet
        if ($response->notification_sent) {
            return false;
        }
        
        return $user->id === $response->responder_id || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TicketResponse $response): bool
    {
        // Only the responder or admins can delete responses, and only if not sent yet
        if ($response->notification_sent) {
            return false;
        }
        
        return $user->id === $response->responder_id || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }

    /**
     * Determine whether the user can send the response now.
     */
    public function sendNow(User $user, TicketResponse $response): bool
    {
        // Only the responder or admins can send responses, and only if not sent yet
        if ($response->notification_sent) {
            return false;
        }
        
        return $user->id === $response->responder_id || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }

    /**
     * Determine whether the user can add attachments to the response.
     */
    public function addAttachment(User $user, TicketResponse $response): bool
    {
        // Only the responder or admins can add attachments
        return $user->id === $response->responder_id || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }

    /**
     * Determine whether the user can download attachments from the response.
     */
    public function downloadAttachment(User $user, TicketResponse $response): bool
    {
        $ticket = $response->ticket;
        
        // User can download attachments from responses to their own tickets,
        // assigned staff can download from responses to their assigned tickets,
        // and admins can download from any response
        return $user->id === $ticket->user_id || 
               $user->id === $ticket->assigned_to || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete attachments from the response.
     */
    public function deleteAttachment(User $user, TicketResponse $response): bool
    {
        // Only the user who uploaded the attachment or admins can delete attachments
        // This will be checked in combination with the attachment's uploaded_by field
        return $user->id === $response->responder_id || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }
}
