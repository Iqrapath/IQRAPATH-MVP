<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    /**
     * Create a new controller instance.
     *
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        // Remove authorizeResource call and handle authorization in individual methods
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $notifications = $this->notificationService->getUserNotifications($request->user(), $perPage);
        
        return NotificationResource::collection($notifications);
    }

    /**
     * Store a newly created notification in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:3',
            'message' => 'required|string|min:10',
            'type' => 'required|string',
            'level' => 'required|string|in:info,success,warning,error',
            'action_text' => 'nullable|string',
            'action_url' => 'nullable|string',
            'image_url' => 'nullable|string',
            'recipient_id' => 'required|integer|exists:users,id',
        ]);
        
        // Get the recipient user
        $recipient = User::findOrFail($validated['recipient_id']);
        
        // Create the notification using the service
        $notificationType = 'App\\Notifications\\' . $validated['type'];
        $notificationData = [
            'title' => $validated['title'],
            'message' => $validated['message'],
            'action_text' => $validated['action_text'] ?? null,
            'action_url' => $validated['action_url'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ];
        
        $notification = $this->notificationService->createNotification(
            $recipient,
            $notificationType,
            $notificationData,
            $validated['level']
        );
        
        return response()->json([
            'message' => 'Notification created successfully',
            'notification' => new NotificationResource($notification)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Notification $notification): NotificationResource
    {
        // Check if the user owns this notification
        if ($notification->notifiable_type !== get_class(request()->user()) || 
            $notification->notifiable_id !== request()->user()->id) {
            abort(403, 'Unauthorized access to notification');
        }
        
        return new NotificationResource($notification);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        try {
            $notification = \App\Models\Notification::findOrFail($notificationId);
            
            // Check if the authenticated user owns this notification
            if ($notification->notifiable_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            
            $notification->markAsRead();
            
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'notification' => $notification
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user());
        
        return response()->json([
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Notification $notification): JsonResponse
    {
        // Check if the user owns this notification
        if ($notification->notifiable_type !== get_class(request()->user()) || 
            $notification->notifiable_id !== request()->user()->id) {
            return response()->json(['error' => 'Unauthorized access to notification'], 403);
        }
        
        $this->notificationService->deleteNotification($notification);
        
        return response()->json([
            'message' => 'Notification deleted successfully'
        ]);
    }
}
