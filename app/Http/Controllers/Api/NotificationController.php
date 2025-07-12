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
        $recipient = NotificationRecipient::findOrFail($id);
        
        // Ensure the user can only mark their own notifications as read
        if ($recipient->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $recipient->markAsRead();
        
        return response()->json([
            'success' => true,
            'unread_count' => $request->user()->unreadNotifications()->count(),
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
} 