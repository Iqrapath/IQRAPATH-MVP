<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuardianMessage;
use App\Models\TeachingSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherSidebarController extends Controller
{
    /**
     * Get data for the teacher sidebar.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSidebarData()
    {
        $user = Auth::user();
        $teacher = $user->teacherProfile;

        // Get pending session requests
        $sessionRequests = TeachingSession::with('student.user')
            ->where('teacher_id', $user->id)
            ->where('status', 'pending_confirmation')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'student' => [
                        'id' => $session->student->user->id,
                        'name' => $session->student->user->name,
                        'avatar' => $session->student->user->avatar,
                        'is_online' => $session->student->user->isOnline(),
                    ],
                    'subject' => $session->subject->name,
                    'scheduled_at' => $session->scheduled_at->format('M d, Y h:i A'),
                    'time_ago' => $session->created_at->diffForHumans(),
                ];
            });

        // Get unread messages
        $messages = GuardianMessage::with('sender.user')
            ->where('recipient_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'sender' => [
                        'id' => $message->sender->user->id,
                        'name' => $message->sender->user->name,
                        'avatar' => $message->sender->user->avatar,
                        'is_online' => $message->sender->user->isOnline(),
                    ],
                    'message' => \Illuminate\Support\Str::limit($message->message, 50),
                    'time_ago' => $message->created_at->diffForHumans(),
                    'is_read' => $message->is_read,
                ];
            });

        // Get online students
        $onlineStudents = User::where('role', 'student')
            ->whereHas('studentProfile.teachingSessions', function ($query) use ($user) {
                $query->where('teacher_id', $user->id);
            })
            ->where(function ($query) {
                $query->whereNotNull('last_active_at')
                    ->where('last_active_at', '>', now()->subMinutes(5));
            })
            ->take(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'is_online' => true,
                ];
            });

        // Count all pending requests
        $pendingRequestCount = TeachingSession::where('teacher_id', $user->id)
            ->where('status', 'pending_confirmation')
            ->count();

        // Count all unread messages
        $unreadMessageCount = GuardianMessage::where('recipient_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'session_requests' => $sessionRequests,
            'messages' => $messages,
            'online_students' => $onlineStudents,
            'pending_request_count' => $pendingRequestCount,
            'unread_message_count' => $unreadMessageCount,
        ]);
    }

    /**
     * Accept a session request.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptSessionRequest($id)
    {
        $user = Auth::user();
        $session = TeachingSession::where('id', $id)
            ->where('teacher_id', $user->id)
            ->where('status', 'pending_confirmation')
            ->firstOrFail();

        $session->status = 'confirmed';
        $session->save();

        return response()->json(['message' => 'Session request accepted successfully']);
    }

    /**
     * Decline a session request.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function declineSessionRequest($id)
    {
        $user = Auth::user();
        $session = TeachingSession::where('id', $id)
            ->where('teacher_id', $user->id)
            ->where('status', 'pending_confirmation')
            ->firstOrFail();

        $session->status = 'declined';
        $session->save();

        return response()->json(['message' => 'Session request declined successfully']);
    }
} 