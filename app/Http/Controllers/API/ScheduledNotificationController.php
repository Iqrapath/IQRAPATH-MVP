<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduledNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduledNotificationController extends Controller
{
    /**
     * Get all scheduled notifications
     */
    public function index()
    {
        try {
            $notifications = ScheduledNotification::orderBy('scheduled_date', 'asc')->get();
            
            return response()->json([
                'success' => true,
                'notifications' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch scheduled notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search and filter scheduled notifications
     */
    public function search(Request $request)
    {
        try {
            $query = ScheduledNotification::query();

            // Search by message
            if ($request->has('search') && $request->search) {
                $query->where('message', 'like', '%' . $request->search . '%');
            }

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by subject (message type)
            if ($request->has('subject') && $request->subject !== 'all') {
                $query->where('message', 'like', '%' . $request->subject . '%');
            }

            // Filter by rating (priority)
            if ($request->has('rating') && $request->rating !== 'all') {
                // You can add a priority field to the model if needed
                // $query->where('priority', $request->rating);
            }

            $notifications = $query->orderBy('scheduled_date', 'asc')->get();

            return response()->json([
                'success' => true,
                'notifications' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new scheduled notification
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'scheduled_date' => 'required|date|after:now',
                'message' => 'required|string|max:500',
                'target_audience' => 'required|string|max:100',
                'frequency' => 'required|string|in:one-time,daily,weekly,monthly',
                'status' => 'sometimes|string|in:scheduled,sent,cancelled'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $notification = ScheduledNotification::create([
                'scheduled_date' => $request->scheduled_date,
                'message' => $request->message,
                'target_audience' => $request->target_audience,
                'frequency' => $request->frequency,
                'status' => $request->status ?? 'scheduled',
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Scheduled notification created successfully',
                'notification' => $notification
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create scheduled notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific scheduled notification
     */
    public function show($id)
    {
        try {
            $notification = ScheduledNotification::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'notification' => $notification
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Scheduled notification not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update a scheduled notification
     */
    public function update(Request $request, $id)
    {
        try {
            $notification = ScheduledNotification::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'scheduled_date' => 'sometimes|date|after:now',
                'message' => 'sometimes|string|max:500',
                'target_audience' => 'sometimes|string|max:100',
                'frequency' => 'sometimes|string|in:one-time,daily,weekly,monthly',
                'status' => 'sometimes|string|in:scheduled,sent,cancelled'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $notification->update($request->only([
                'scheduled_date', 'message', 'target_audience', 'frequency', 'status'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Scheduled notification updated successfully',
                'notification' => $notification
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update scheduled notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a scheduled notification
     */
    public function cancel($id)
    {
        try {
            $notification = ScheduledNotification::findOrFail($id);
            
            if ($notification->status !== 'scheduled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only scheduled notifications can be cancelled'
                ], 400);
            }

            $notification->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Scheduled notification cancelled successfully',
                'notification' => $notification
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel scheduled notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a scheduled notification
     */
    public function destroy($id)
    {
        try {
            $notification = ScheduledNotification::findOrFail($id);
            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Scheduled notification deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete scheduled notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
