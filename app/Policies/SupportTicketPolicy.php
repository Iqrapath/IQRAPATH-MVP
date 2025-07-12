<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SupportTicketPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Anyone can view support tickets list, but what they see will be filtered in the controller
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SupportTicket $ticket): bool
    {
        // User can view their own tickets, assigned staff can view their assigned tickets,
        // and admins can view any ticket
        return $user->id === $ticket->user_id || 
               $user->id === $ticket->assigned_to || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create a support ticket
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SupportTicket $ticket): bool
    {
        // Only assigned staff or admins can update tickets
        return $user->id === $ticket->assigned_to || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }

    /**
     * Determine whether the user can respond to the ticket.
     */
    public function respond(User $user, SupportTicket $ticket): bool
    {
        // User can respond to their own tickets, assigned staff can respond to their assigned tickets,
        // and admins can respond to any ticket
        return $user->id === $ticket->user_id || 
               $user->id === $ticket->assigned_to || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SupportTicket $ticket): bool
    {
        // Only admins can delete tickets
        return $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can manage support tickets.
     */
    public function manage(User $user): bool
    {
        // Only admins can manage support tickets
        return $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can add attachments to the ticket.
     */
    public function addAttachment(User $user, SupportTicket $ticket): bool
    {
        // User can add attachments to their own tickets, assigned staff can add to their assigned tickets,
        // and admins can add to any ticket
        return $user->id === $ticket->user_id || 
               $user->id === $ticket->assigned_to || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }

    /**
     * Determine whether the user can download attachments from the ticket.
     */
    public function downloadAttachment(User $user, SupportTicket $ticket): bool
    {
        // User can download attachments from their own tickets, assigned staff can download from their assigned tickets,
        // and admins can download from any ticket
        return $user->id === $ticket->user_id || 
               $user->id === $ticket->assigned_to || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete attachments from the ticket.
     */
    public function deleteAttachment(User $user, SupportTicket $ticket): bool
    {
        // Only the user who uploaded the attachment, assigned staff, or admins can delete attachments
        // This will be checked in combination with the attachment's uploaded_by field
        return $user->id === $ticket->user_id || 
               $user->id === $ticket->assigned_to || 
               $user->role === 'super-admin' || 
               $user->role === 'admin';
    }

    /**
     * Determine whether the user can assign the ticket.
     */
    public function assign(User $user, SupportTicket $ticket): bool
    {
        // Only admins can assign tickets
        return $user->role === 'super-admin' || $user->role === 'admin';
    }
}
