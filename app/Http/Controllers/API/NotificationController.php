<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
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
     * Mark the specified notification as read.
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        // Check if the user owns this notification
        if ($notification->notifiable_type !== get_class(request()->user()) || 
            $notification->notifiable_id !== request()->user()->id) {
            return response()->json(['error' => 'Unauthorized access to notification'], 403);
        }
        
        $this->notificationService->markAsRead($notification);
        
        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => new NotificationResource($notification)
        ]);
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
