<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\UrgentAction;
use App\Models\ScheduledNotification;
use App\Models\TeachingSession;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationsController extends Controller
{
    public function index()
    {
        // Get notifications with pagination and ordering
        $notifications = Notification::with('notifiable')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get urgent actions for the current user
        $user = auth()->user();
        $urgentActions = UrgentAction::getForUser($user)
            ->map(function ($action) {
                // Calculate real-time count
                $realCount = $action->calculateRealCount();
                
                return [
                    'id' => $action->id,
                    'title' => $action->title,
                    'count' => $realCount,
                    'actionText' => $action->action_text,
                    'actionUrl' => $action->action_url,
                ];
            })
            ->filter(function ($action) {
                // Only show actions with count > 0
                return $action['count'] > 0;
            })
            ->values()
            ->toArray();

        // Get scheduled notifications for the current user
        $scheduledNotifications = ScheduledNotification::orderBy('scheduled_date', 'asc')->get();

        // Get completed classes with relationships
        $completedClasses = TeachingSession::with(['teacher', 'student', 'subject'])
            ->completed()
            ->orderBy('completion_date', 'desc')
            ->limit(50) // Limit to recent 50 for performance
            ->get();

        return Inertia::render('admin/notifications/notifications', [
            'notifications' => $notifications,
            'urgentActions' => $urgentActions,
            'scheduledNotifications' => $scheduledNotifications,
            'completedClasses' => $completedClasses,
        ]);
    }

    public function search(Request $request)
    {
        $query = Notification::with('notifiable');

        // Search by notification data
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                  ->orWhere('data', 'like', "%{$search}%")
                  ->orWhere('level', 'like', "%{$search}%");
            });
        }

        // Filter by level (role equivalent)
        if ($request->has('role') && $request->role && $request->role !== 'all') {
            $query->where('level', $request->role);
        }

        // Filter by read status
        if ($request->has('status') && $request->status && $request->status !== 'all') {
            if ($request->status === 'read') {
                $query->whereNotNull('read_at');
            } elseif ($request->status === 'unread') {
                $query->whereNull('read_at');
            }
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($notifications);
    }
}
