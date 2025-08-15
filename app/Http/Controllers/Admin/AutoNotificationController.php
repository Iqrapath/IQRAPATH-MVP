<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTrigger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AutoNotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationTrigger::query();

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->has('status') && $request->status && $request->status !== 'all') {
            if ($request->status === 'enabled') {
                $query->where('is_enabled', true);
            } elseif ($request->status === 'disabled') {
                $query->where('is_enabled', false);
            }
        }

        // Filter by subject (event type)
        if ($request->has('subject') && $request->subject && $request->subject !== 'all') {
            $query->where('event', 'like', '%' . $request->subject . '%');
        }

        // Filter by rating (level)
        if ($request->has('rating') && $request->rating && $request->rating !== 'all') {
            $query->where('level', $request->rating);
        }

        $notifications = $query->orderBy('name')->get();

        return Inertia::render('admin/notifications/auto-triggers', [
            'notifications' => $notifications,
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? 'all',
                'subject' => $request->subject ?? 'all',
                'rating' => $request->rating ?? 'all',
            ],
        ]);
    }

    public function update(Request $request, NotificationTrigger $notificationTrigger)
    {
        $validated = $request->validate([
            'is_enabled' => 'required|boolean',
        ]);

        $notificationTrigger->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Notification status updated successfully',
        ]);
    }

    public function destroy(NotificationTrigger $notificationTrigger)
    {
        $notificationTrigger->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully',
        ]);
    }
}
