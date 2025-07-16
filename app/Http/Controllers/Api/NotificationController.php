<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageReceived;
use App\Events\NotificationReceived;
use App\Events\SessionRequestReceived;
use App\Http\Controllers\Controller;
use App\Models\GuardianMessage;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\Subject;
use App\Models\TeachingSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                // Check if content is personalized
                $isPersonalized = isset($recipient->personalized_content['title']) || 
                                 isset($recipient->personalized_content['body']);
                                 
                return [
                    'id' => $recipient->id,
                    'title' => $recipient->personalized_title,
                    'body' => $recipient->personalized_body,
                    'type' => $recipient->notification->type,
                    'status' => $recipient->status,
                    'created_at' => $recipient->created_at,
                    'read_at' => $recipient->read_at,
                    'is_personalized' => $isPersonalized,
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
    
    /**
     * Test sending a notification via real-time channels.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testNotification(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);
        
        $sender = Auth::user();
        $recipient = User::findOrFail($request->recipient_id);
        
        // Create a notification
        $notification = Notification::create([
            'title' => 'Test Notification from ' . $sender->name,
            'body' => $request->message,
            'type' => 'system',
            'status' => 'sent',
            'sender_id' => $sender->id,
            'sender_type' => 'user',
            'sent_at' => now(),
        ]);
        
        // Create a notification recipient
        $notificationRecipient = NotificationRecipient::create([
            'notification_id' => $notification->id,
            'user_id' => $recipient->id,
            'status' => 'delivered',
            'channel' => 'in-app',
            'delivered_at' => now(),
        ]);
        
        // The event will be automatically dispatched by the model observer
        
        return response()->json([
            'success' => true,
            'message' => 'Test notification sent successfully',
        ]);
    }
    
    /**
     * Test sending a session request via real-time channels.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testSessionRequest(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'subject' => 'required|string',
        ]);
        
        $student = Auth::user();
        $teacher = User::findOrFail($request->teacher_id);
        
        if (!$teacher->hasRole('teacher')) {
            return response()->json([
                'error' => 'The specified user is not a teacher',
            ], 422);
        }
        
        // Find or create a subject
        $subject = Subject::firstOrCreate(
            ['name' => $request->subject],
            ['description' => 'Test subject for ' . $request->subject]
        );
        
        // Create a teaching session
        $session = TeachingSession::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'session_date' => now()->addDays(1)->format('Y-m-d'),
            'start_time' => now()->addDays(1)->format('H:i'),
            'end_time' => now()->addDays(1)->addHour()->format('H:i'),
            'status' => 'pending_confirmation',
        ]);
        
        // The event will be automatically dispatched by the model observer
        
        return response()->json([
            'success' => true,
            'message' => 'Test session request sent successfully',
        ]);
    }
    
    /**
     * Test sending a direct message via real-time channels.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testMessage(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);
        
        $sender = Auth::user();
        $recipient = User::findOrFail($request->recipient_id);
        
        // Create a message
        $message = GuardianMessage::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'message' => $request->message,
            'is_read' => false,
        ]);
        
        // The event will be automatically dispatched by the model observer
        
        return response()->json([
            'success' => true,
            'message' => 'Test message sent successfully',
        ]);
    }

    /**
     * Mark a notification recipient as read directly using the recipient ID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markRecipientAsRead(Request $request, $id)
    {
        $user = $request->user();
        
        // Find the notification recipient by its ID
        $recipient = NotificationRecipient::where('id', $id)
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
     * Get just the unread notification count for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserNotificationCount(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Get the user's notifications with pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserNotifications(Request $request)
    {
        $user = Auth::user();
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 5);
        
        $notifications = NotificationRecipient::where('user_id', $user->id)
            ->with('notification')
            ->where('channel', 'in-app')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
            
        $formattedNotifications = $notifications->items()->map(function ($recipient) {
            // Check if content is personalized
            $isPersonalized = isset($recipient->personalized_content['title']) || 
                             isset($recipient->personalized_content['body']);
                             
            return [
                'id' => $recipient->id,
                'title' => $recipient->personalized_title,
                'body' => $recipient->personalized_body,
                'type' => $recipient->notification->type,
                'status' => $recipient->status,
                'is_read' => $recipient->read_at !== null,
                'created_at' => $recipient->created_at->diffForHumans(),
                'is_personalized' => $isPersonalized,
            ];
        });
            
        return response()->json([
            'notifications' => $formattedNotifications,
            'unread_count' => NotificationRecipient::where('user_id', $user->id)
                ->where('channel', 'in-app')
                ->where('read_at', null)
                ->count(),
            'pagination' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'from' => $notifications->firstItem(),
                'to' => $notifications->lastItem(),
            ],
        ]);
    }
    
    /**
     * Get notifications for the admin dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAdminNotifications(Request $request)
    {
        $user = Auth::user();
        $limit = $request->input('limit', 5);
        
        if ($user->role !== 'admin' && $user->role !== 'super-admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $notifications = NotificationRecipient::where('user_id', $user->id)
            ->with('notification')
            ->where('channel', 'in-app')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($recipient) {
                // Check if content is personalized
                $isPersonalized = isset($recipient->personalized_content['title']) || 
                                 isset($recipient->personalized_content['body']);
                                 
                return [
                    'id' => $recipient->id,
                    'title' => $recipient->personalized_title,
                    'body' => $recipient->personalized_body,
                    'type' => $recipient->notification->type,
                    'status' => $recipient->status,
                    'is_read' => $recipient->read_at !== null,
                    'created_at' => $recipient->created_at->diffForHumans(),
                    'is_personalized' => $isPersonalized,
                ];
            });
            
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => NotificationRecipient::where('user_id', $user->id)
                ->where('channel', 'in-app')
                ->where('read_at', null)
                ->count(),
        ]);
    }
} 