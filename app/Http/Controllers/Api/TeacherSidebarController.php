<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeachingSession;
use App\Models\GuardianMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TeacherSidebarController extends Controller
{
    /**
     * Get data for teacher right sidebar
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSidebarData(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isTeacher()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Get pending session requests
        $sessionRequests = TeachingSession::where('teacher_id', $user->id)
            ->where('status', 'requested')
            ->with('student:id,name,avatar,status_type,last_active_at')
            ->with('subject:id,name')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'student' => [
                        'id' => $session->student->id,
                        'name' => $session->student->name,
                        'avatar' => $session->student->avatar,
                        'is_online' => $session->student->isOnline(),
                    ],
                    'subject' => $session->subject->name,
                    'scheduled_at' => $session->scheduled_at,
                    'created_at' => $session->created_at,
                    'time_ago' => $this->formatTimeAgo($session->created_at),
                ];
            });
        
        // Get recent messages
        $messages = GuardianMessage::where('recipient_id', $user->id)
            ->with('sender:id,name,avatar,status_type,last_active_at')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name,
                        'avatar' => $message->sender->avatar,
                        'is_online' => $message->sender->isOnline(),
                    ],
                    'message' => $message->message,
                    'created_at' => $message->created_at,
                    'time_ago' => $this->formatTimeAgo($message->created_at),
                    'is_read' => $message->is_read,
                ];
            });
        
        // Get online students
        $onlineStudents = User::whereHas('studentProfile')
            ->whereHas('teachingSessions', function ($query) use ($user) {
                $query->where('teacher_id', $user->id)
                      ->whereIn('status', ['scheduled', 'completed']);
            })
            ->where('status_type', 'online')
            ->select('id', 'name', 'avatar')
            ->take(5)
            ->get();
        
        return response()->json([
            'session_requests' => $sessionRequests,
            'messages' => $messages,
            'online_students' => $onlineStudents,
            'unread_message_count' => GuardianMessage::where('recipient_id', $user->id)
                                        ->where('is_read', false)
                                        ->count(),
            'pending_request_count' => TeachingSession::where('teacher_id', $user->id)
                                        ->where('status', 'requested')
                                        ->count(),
        ]);
    }
    
    /**
     * Format time ago from timestamp
     * 
     * @param  \Carbon\Carbon  $timestamp
     * @return string
     */
    private function formatTimeAgo($timestamp)
    {
        $now = Carbon::now();
        $diff = $timestamp->diffInSeconds($now);
        
        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' ' . ($minutes == 1 ? 'min' : 'mins') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' ' . ($hours == 1 ? 'hour' : 'hours') . ' ago';
        } elseif ($diff < 172800) {
            return 'Yesterday';
        } else {
            return $timestamp->format('M d');
        }
    }
    
    /**
     * Accept a session request
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptSessionRequest(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user->isTeacher()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $session = TeachingSession::where('id', $id)
            ->where('teacher_id', $user->id)
            ->where('status', 'requested')
            ->first();
            
        if (!$session) {
            return response()->json(['error' => 'Session request not found'], 404);
        }
        
        $session->status = 'scheduled';
        $session->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Session request accepted',
        ]);
    }
    
    /**
     * Decline a session request
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function declineSessionRequest(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user->isTeacher()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $session = TeachingSession::where('id', $id)
            ->where('teacher_id', $user->id)
            ->where('status', 'requested')
            ->first();
            
        if (!$session) {
            return response()->json(['error' => 'Session request not found'], 404);
        }
        
        $session->status = 'declined';
        $session->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Session request declined',
        ]);
    }
} 