<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationViewController extends Controller
{
    /**
     * Display a listing of the admin's notifications.
     *
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get pagination parameters
        $perPage = $request->input('per_page', 10);
        
        // Get search and filter parameters
        $search = $request->input('search');
        $type = $request->input('type');
        $status = $request->input('status');
        
        // Query the notification recipients for this admin
        $query = NotificationRecipient::where('user_id', $user->id)
            ->where('channel', 'in-app')
            ->with('notification');
            
        // Apply search filter if provided
        if ($search) {
            $query->whereHas('notification', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }
        
        // Apply type filter if provided
        if ($type && $type !== 'all') {
            $query->whereHas('notification', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }
        
        // Apply status filter if provided
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        // Get paginated results
        $notifications = $query->orderBy('created_at', 'desc')
                              ->paginate($perPage)
                              ->withQueryString();
        
        // Format the notifications for the frontend
        $formattedNotifications = [
            'data' => collect($notifications->items())->map(function ($recipient) {
                return [
                    'id' => $recipient->id,
                    'title' => $recipient->notification->title,
                    'body' => $recipient->notification->body,
                    'type' => $recipient->notification->type,
                    'status' => $recipient->status,
                    'created_at' => $recipient->created_at->diffForHumans(),
                ];
            })->toArray(),
            'links' => [
                'prev' => $notifications->previousPageUrl(),
                'next' => $notifications->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'from' => $notifications->firstItem() ?? 0,
                'last_page' => $notifications->lastPage(),
                'links' => $notifications->linkCollection()->map(function ($link) {
                    return [
                        'url' => $link['url'],
                        'label' => $link['label'],
                        'active' => $link['active'],
                    ];
                })->toArray(),
                'path' => $notifications->path(),
                'per_page' => $notifications->perPage(),
                'to' => $notifications->lastItem() ?? 0,
                'total' => $notifications->total(),
            ],
        ];
        
        return Inertia::render('admin/notification', [
            'notifications' => $formattedNotifications,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status,
            ],
        ]);
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
        
        return Inertia::render('admin/notifications/show', [
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
        
        return redirect()->route('admin.notification');
    }
} 