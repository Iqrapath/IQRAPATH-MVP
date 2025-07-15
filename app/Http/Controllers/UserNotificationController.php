<?php

namespace App\Http\Controllers;

use App\Models\NotificationRecipient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserNotificationController extends Controller
{
    /**
     * Display a listing of the user's notifications.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        // This route is not used directly as we have role-specific notification pages
        // Instead, we redirect to the appropriate role-specific notification page
        $user = $request->user();
        
        return match($user->role) {
            'teacher' => redirect()->route('teacher.notifications'),
            'student' => redirect()->route('student.notifications'),
            'guardian' => redirect()->route('guardian.notifications'),
            'super-admin' => redirect()->route('admin.notification.index'),
            default => redirect()->route('dashboard'),
        };
    }

    /**
     * Display the specified notification.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    public function show($id, Request $request)
    {
        $user = $request->user();
        
        // Find the notification recipient for this user
        $recipient = NotificationRecipient::where('id', $id)
            ->where('user_id', $user->id)
            ->with('notification')
            ->first();
        
        if (!$recipient) {
            return redirect()->back()->with('error', 'Notification not found');
        }
        
        // Mark as read
        $recipient->markAsRead();
        
        // Return the appropriate view based on user role
        return match($user->role) {
            'teacher' => Inertia::render('teacher/notifications/show', [
                'notification' => [
                    'id' => $recipient->id,
                    'title' => $recipient->notification->title,
                    'body' => $recipient->notification->body,
                    'type' => $recipient->notification->type,
                    'created_at' => $recipient->created_at,
                    'read_at' => $recipient->read_at,
                ]
            ]),
            'student' => Inertia::render('student/notifications/show', [
                'notification' => [
                    'id' => $recipient->id,
                    'title' => $recipient->notification->title,
                    'body' => $recipient->notification->body,
                    'type' => $recipient->notification->type,
                    'created_at' => $recipient->created_at,
                    'read_at' => $recipient->read_at,
                ]
            ]),
            'guardian' => Inertia::render('guardian/notifications/show', [
                'notification' => [
                    'id' => $recipient->id,
                    'title' => $recipient->notification->title,
                    'body' => $recipient->notification->body,
                    'type' => $recipient->notification->type,
                    'created_at' => $recipient->created_at,
                    'read_at' => $recipient->read_at,
                ]
            ]),
            default => redirect()->route('dashboard'),
        };
    }

    /**
     * Mark a notification as read.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAsRead($id, Request $request)
    {
        $user = $request->user();
        
        // Find the notification recipient for this user
        $recipient = NotificationRecipient::where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$recipient) {
            return redirect()->back()->with('error', 'Notification not found');
        }
        
        $recipient->markAsRead();
        
        return redirect()->back()->with('success', 'Notification marked as read');
    }

    /**
     * Mark all notifications as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->markAllNotificationsAsRead();
        
        return redirect()->back()->with('success', 'All notifications marked as read');
    }

    /**
     * Remove the specified notification.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id, Request $request)
    {
        $user = $request->user();
        
        // Find the notification recipient for this user
        $recipient = NotificationRecipient::where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$recipient) {
            return redirect()->back()->with('error', 'Notification not found');
        }
        
        // Update status to deleted
        $recipient->status = 'deleted';
        $recipient->save();
        
        return redirect()->back()->with('success', 'Notification deleted');
    }
} 