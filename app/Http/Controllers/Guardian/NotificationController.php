<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Display a listing of the guardian's notifications.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        return Inertia::render('guardian/notifications');
    }

    /**
     * Display the specified notification.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function show($id)
    {
        // Get the notification details
        $notification = NotificationRecipient::with('notification')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        // Mark as read if not already
        if (!$notification->read_at) {
            $notification->markAsRead();
        }
        
        // Format the notification data for the frontend
        $formattedNotification = [
            'id' => $notification->id,
            'title' => $notification->notification->title,
            'body' => $notification->notification->body,
            'type' => $notification->notification->type,
            'status' => $notification->status,
            'created_at' => $notification->created_at,
            'sender' => $notification->notification->sender_id ? User::find($notification->notification->sender_id) : null,
        ];
        
        return Inertia::render('guardian/notifications/show', [
            'notification' => $formattedNotification,
            'currentUser' => Auth::user(),
        ]);
    }
    
    /**
     * Mark a notification as read.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAsRead($id)
    {
        $notification = NotificationRecipient::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        $notification->markAsRead();
        
        return redirect()->back();
    }
    
    /**
     * Mark all notifications as read.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        // Update all unread notifications for this user
        NotificationRecipient::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        
        return redirect()->back();
    }
    
    /**
     * Delete a notification.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $notification = NotificationRecipient::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        $notification->delete();
        
        return redirect()->route('guardian.notifications');
    }
} 