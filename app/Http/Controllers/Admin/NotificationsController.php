<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\UrgentAction;
use App\Models\ScheduledNotification;
use App\Models\TeachingSession;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Carbon;

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

		// Get notification templates and users for forms
		$templates = NotificationTemplate::active()->orderBy('name')->get();
		$users = User::select('id', 'name', 'email', 'role')->orderBy('name')->get();

		return Inertia::render('admin/notifications/notifications', [
			'notifications' => $notifications,
			'urgentActions' => $urgentActions,
			'scheduledNotifications' => $scheduledNotifications,
			'completedClasses' => $completedClasses,
			'templates' => $templates,
			'users' => $users,
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

	public function show(Notification $notification)
	{
		// Build a grouping key based on content to approximate the campaign/batch
		$data = $notification->data ?? [];
		$title = $data['title'] ?? null;
		$body = $data['body'] ?? ($data['message'] ?? null);
		$actionText = $data['action_text'] ?? ($notification->action_text ?? null);
		$actionUrl = $data['action_url'] ?? ($notification->action_url ?? null);

		$windowStart = (clone $notification->created_at)->subMinutes(15);
		$windowEnd = (clone $notification->created_at)->addMinutes(15);

		$groupQuery = Notification::query()
			->where('type', $notification->type)
			->when(!empty($notification->level), fn($q) => $q->where('level', $notification->level))
			->when(!empty($notification->channel), fn($q) => $q->where('channel', $notification->channel))
			->when(!empty($title), fn($q) => $q->where('data->title', $title))
			->when(!empty($body), function ($q) use ($body) {
				$q->where(function ($inner) use ($body) {
					$inner->where('data->body', $body)
						->orWhere('data->message', $body);
				});
			})
			->when(!empty($actionText), fn($q) => $q->where('data->action_text', $actionText))
			->when(!empty($actionUrl), fn($q) => $q->where('data->action_url', $actionUrl))
			->whereBetween('created_at', [$windowStart, $windowEnd]);

		$groupNotifications = $groupQuery->get(['id','notifiable_id','read_at','data']);
		$recipientIds = $groupNotifications->pluck('notifiable_id')->filter()->unique()->values();
		$recipientCount = $recipientIds->count();

		$readCount = $groupNotifications->whereNotNull('read_at')->count();
		$failedCount = 0; // For in-app notifications, presence in DB implies delivery
		$deliveredCount = $recipientCount; // Consider all saved rows as delivered

		$deliveryStatus = 'pending';
		if ($recipientCount > 0 && $failedCount === $recipientCount) {
			$deliveryStatus = 'failed';
		} elseif ($deliveredCount > 0) {
			$deliveryStatus = 'delivered';
		}

		$openRate = $recipientCount > 0 ? round(($readCount / $recipientCount) * 100, 0) : 0;
		$clickThroughRate = 0; // Not tracked yet without adding schema; keep simple

		$users = User::whereIn('id', $recipientIds)->get(['id','name','email'])->keyBy('id');
		$recipients = $groupNotifications
			->take(100)
			->map(function ($n) use ($users) {
				$user = $users[$n->notifiable_id] ?? null;
				return [
					'id' => (string)($user->id ?? $n->notifiable_id),
					'name' => $user->name ?? 'Unknown',
					'email' => $user->email ?? 'unknown',
					'status' => $n->read_at ? 'read' : 'delivered',
				];
			})
			->values()
			->toArray();

		// Attach analytics to the notification payload sent to Inertia
		$notification->recipient_count = $recipientCount;
		$notification->delivery_status = $deliveryStatus;
		$notification->open_rate = $openRate;
		$notification->click_through_rate = $clickThroughRate;
		$notification->recipients = $recipients;

		return Inertia::render('admin/notifications/notification-details', [
			'notification' => $notification,
		]);
	}

	public function resend(\App\Models\Notification $notification, Request $request)
	{
		try {
			// Get the original notification data
			$data = $notification->data ?? [];
			$title = $data['title'] ?? $notification->type;
			$body = $data['body'] ?? ($data['message'] ?? '');
			$actionText = $data['action_text'] ?? ($notification->action_text ?? null);
			$actionUrl = $data['action_url'] ?? ($notification->action_url ?? null);

			// Find all recipients of the original notification (within time window)
			$windowStart = (clone $notification->created_at)->subMinutes(15);
			$windowEnd = (clone $notification->created_at)->addMinutes(15);

			$groupNotifications = Notification::where('type', $notification->type)
				->when(!empty($notification->level), fn($q) => $q->where('level', $notification->level))
				->when(!empty($notification->channel), fn($q) => $q->where('channel', $notification->channel))
				->when(!empty($title), fn($q) => $q->where('data->title', $title))
				->when(!empty($body), function ($q) use ($body) {
					$q->where(function ($inner) use ($body) {
						$inner->where('data->body', $body)
							->orWhere('data->message', $body);
					});
				})
				->when(!empty($actionText), fn($q) => $q->where('data->action_text', $actionText))
				->when(!empty($actionUrl), fn($q) => $q->where('data->action_url', $actionUrl))
				->whereBetween('created_at', [$windowStart, $windowEnd])
				->get();

			$recipientIds = $groupNotifications->pluck('notifiable_id')->filter()->unique()->values();
			$users = User::whereIn('id', $recipientIds)->get();

			// Create new notifications for each recipient
			$notificationService = app(\App\Services\NotificationService::class);
			$resendCount = 0;

			foreach ($users as $user) {
				$newNotification = $notificationService->createNotification(
					$user,
					$notification->type . '_resend',
					[
						'title' => $title,
						'body' => $body,
						'action_text' => $actionText,
						'action_url' => $actionUrl,
						'original_notification_id' => $notification->id,
						'resend_date' => now()->toISOString(),
					],
					$notification->level
				);

				if ($newNotification) {
					$resendCount++;
				}
			}

			$message = "Notification resent to {$resendCount} recipients";

			if ($request->expectsJson() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
				return response()->json([
					'success' => true,
					'message' => $message,
					'resend_count' => $resendCount
				]);
			}

			return redirect()->back()->with('success', $message);

		} catch (\Exception $e) {
			if ($request->expectsJson() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
				return response()->json([
					'success' => false,
					'message' => 'Failed to resend notification: ' . $e->getMessage()
				], 500);
			}
			return redirect()->back()->with('error', 'Failed to resend notification.');
		}
	}

	public function edit(Notification $notification)
	{
		// Get notification data for editing
		$data = $notification->data ?? [];
		$title = $data['title'] ?? $notification->type;
		$body = $data['body'] ?? ($data['message'] ?? '');
		$actionText = $data['action_text'] ?? ($notification->action_text ?? '');
		$actionUrl = $data['action_url'] ?? ($notification->action_url ?? '');
		
		return Inertia::render('admin/notifications/edit', [
			'notification' => [
				'id' => $notification->id,
				'level' => $notification->level,
				'channel' => $notification->channel,
				'type' => $notification->type,
				'action_text' => $actionText,
				'action_url' => $actionUrl,
				'data' => [
					'title' => $title,
					'body' => $body,
					'action_text' => $actionText,
					'action_url' => $actionUrl,
				],
			],
			'templates' => NotificationTemplate::active()->orderBy('name')->get(),
			'users' => User::select('id', 'name', 'email', 'role')->orderBy('name')->get(),
		]);
	}

	public function destroy($notification, Request $request)
	{
		try {
			// Find by ID manually to avoid route model binding exceptions and handle missing records gracefully
			$model = \App\Models\Notification::find($notification);
			if (!$model) {
				if ($request->expectsJson() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
					return response()->json([
						'success' => true,
						'message' => 'Notification already deleted or not found'
					]);
				}
				return redirect()->route('admin.notifications.index')->with('success', 'Notification already deleted or not found');
			}

			$model->delete();

			if ($request->expectsJson() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
				return response()->json([
					'success' => true,
					'message' => 'Notification deleted successfully'
				]);
			}

			return redirect()->route('admin.notifications.index')->with('success', 'Notification deleted successfully');

		} catch (\Exception $e) {
			if ($request->expectsJson() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
				return response()->json([
					'success' => false,
					'message' => 'Failed to delete notification: ' . $e->getMessage()
				], 500);
			}
			return redirect()->back()->with('error', 'Failed to delete notification.');
		}
	}
}
