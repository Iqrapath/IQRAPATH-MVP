<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationRedirectController extends Controller
{
    /**
     * Redirect to the appropriate notification page based on user role
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function redirect(Request $request, $id)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Find the notification recipient
        $notification = NotificationRecipient::with('notification')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();
            
        if (!$notification) {
            // If notification doesn't exist or doesn't belong to this user,
            // redirect to notifications list
            return $this->redirectToNotificationsList($user->role);
        }
        
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
        
        // Return the appropriate view based on user role
        switch ($user->role) {
            case 'teacher':
                return redirect()->route('teacher.notification.show', ['id' => $id]);
            case 'student':
                return redirect()->route('student.notification.show', ['id' => $id]);
            case 'guardian':
                return redirect()->route('guardian.notification.show', ['id' => $id]);
            case 'admin':
            case 'super-admin':
                // For admin, we need to use the correct route name
                // The route is defined as /admin/notification/{notification}
                // But the parameter is named 'notification', not 'id'
                return redirect()->route('admin.notification.show', ['notification' => $id]);
                // return redirect("/admin/notification/{$id}");
            default:
                return redirect()->route('dashboard');
        }
    }
    
    /**
     * Helper method to redirect to the appropriate notifications list page
     *
     * @param string $role
     * @return \Illuminate\Http\RedirectResponse
     */
    private function redirectToNotificationsList($role)
    {
        switch ($role) {
            case 'teacher':
                return redirect()->route('teacher.notifications');
            case 'student':
                return redirect()->route('student.notifications');
            case 'guardian':
                return redirect()->route('guardian.notifications');
            case 'admin':
            case 'super-admin':
                // For admin, we need to use the correct route
                return redirect('/admin/notification');
            default:
                return redirect()->route('dashboard');
        }
    }
} 