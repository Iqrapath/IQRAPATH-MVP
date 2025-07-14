<?php

namespace App\Http\Controllers;

use App\Models\NotificationRecipient;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserNotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display the user's notification center.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $notifications = $user->receivedNotifications()
            ->with('notification')
            ->where('channel', 'in-app')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return Inertia::render('notifications/notification-index', [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Show a specific notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function show(Request $request, $id)
    {
        $recipient = NotificationRecipient::with('notification')
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
        
        // Mark as read if not already
        if (!$recipient->read_at) {
            $recipient->markAsRead();
        }
        
        return Inertia::render('notifications/notification-show', [
            'notification' => [
                'id' => $recipient->id,
                'title' => $recipient->notification->title,
                'body' => $recipient->notification->body,
                'type' => $recipient->notification->type,
                'metadata' => $recipient->notification->metadata,
                'created_at' => $recipient->created_at,
                'read_at' => $recipient->read_at,
            ],
        ]);
    }

    /**
     * Mark a notification as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAsRead(Request $request, $id)
    {
        $recipient = NotificationRecipient::where('user_id', $request->user()->id)
            ->findOrFail($id);
        
        $recipient->markAsRead();
        
        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->markAllNotificationsAsRead();
        
        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Delete a notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, $id)
    {
        $recipient = NotificationRecipient::where('user_id', $request->user()->id)
            ->findOrFail($id);
        
        $recipient->delete();
        
        return back()->with('success', 'Notification deleted.');
    }
} 