<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserNotificationController extends Controller
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
    }

    /**
     * Get all notifications for the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $notifications = $this->notificationService->getUserNotifications($request->user(), $perPage);
        
        return NotificationResource::collection($notifications);
    }

    /**
     * Get unread notifications for the authenticated user.
     */
    public function unread(Request $request): AnonymousResourceCollection
    {
        $notifications = $this->notificationService->getUnreadNotifications($request->user());
        
        return NotificationResource::collection($notifications);
    }

    /**
     * Get the count of unread notifications for the authenticated user.
     */
    public function count(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadNotificationCount($request->user());
        
        return response()->json([
            'count' => $count
        ]);
    }
}
