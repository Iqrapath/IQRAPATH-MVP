<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationRecipient;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get the user's notifications.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $user->receivedNotifications()
            ->with('notification')
            ->where('channel', 'in-app')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($recipient) {
                return [
                    'id' => $recipient->id,
                    'title' => $recipient->notification->title,
                    'body' => $recipient->notification->body,
                    'type' => $recipient->notification->type,
                    'status' => $recipient->status,
                    'created_at' => $recipient->created_at,
                    'read_at' => $recipient->read_at,
                ];
            });
            
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a notification as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        
        // Find the notification recipient for this user and notification
        $recipient = NotificationRecipient::where('notification_id', $id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$recipient) {
            return response()->json(['error' => 'Notification not found'], 404);
        }
        
        $recipient->markAsRead();
        
        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark all notifications as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->markAllNotificationsAsRead();
        
        return response()->json([
            'success' => true,
            'unread_count' => 0,
        ]);
    }
    
    /**
     * Create a test notification for the current user.
     * This is only available in local/development environment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTestNotification(Request $request)
    {
        if (!app()->environment(['local', 'development'])) {
            return response()->json(['error' => 'Not available in this environment'], 403);
        }
        
        $user = $request->user();
        $types = ['system', 'payment', 'session', 'reminder'];
        $randomType = $types[array_rand($types)];
        
        // Create a notification
        $notification = \App\Models\Notification::create([
            'title' => 'Test Notification - ' . ucfirst($randomType),
            'body' => 'This is a test notification of type "' . $randomType . '". Created at ' . now()->format('Y-m-d H:i:s'),
            'type' => $randomType,
            'status' => 'sent',
            'sender_type' => 'system',
            'sent_at' => now(),
        ]);
        
        // Add the current user as a recipient
        \App\Models\NotificationRecipient::create([
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'status' => 'delivered',
            'channel' => 'in-app',
            'delivered_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'notification' => $notification,
            'message' => 'Test notification created successfully',
        ]);
    }
} 