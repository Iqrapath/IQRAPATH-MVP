<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeachingSession;
use App\Models\User;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class SidebarController extends Controller
{
    /**
     * Get sidebar data for the teacher dashboard
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized',
                ], 401);
            }
            
            if ($user->role !== 'teacher') {
                return response()->json([
                    'error' => 'Unauthorized - User is not a teacher',
                ], 403);
            }
            
            $teacherProfile = $user->teacherProfile;
            
            if (!$teacherProfile) {
                return response()->json([
                    'error' => 'Teacher profile not found',
                    'session_requests' => [],
                    'messages' => [],
                    'online_students' => [],
                    'unread_message_count' => 0,
                    'pending_request_count' => 0,
                ], 200);
            }
            
            // Initialize default values
            $sessionRequests = [];
            $messages = [];
            $onlineStudents = [];
            $unreadMessageCount = 0;
            $pendingRequestCount = 0;
            
            // Check if TeachingSession model exists
            if (class_exists('App\Models\TeachingSession') && Schema::hasTable('teaching_sessions')) {
                try {
                    // Get pending session requests
                    $sessionRequests = TeachingSession::where('teacher_id', $teacherProfile->id)
                        ->where('status', 'requested')
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->with(['student.user'])
                        ->get()
                        ->map(function ($session) {
                            return [
                                'id' => $session->id,
                                'student' => [
                                    'id' => $session->student->user->id,
                                    'name' => $session->student->user->name,
                                    'avatar' => $session->student->user->profile_photo_url ?? null,
                                    'is_online' => $session->student->user->is_online ?? false,
                                ],
                                'subject' => $session->subject->name ?? 'Unnamed Subject',
                                'scheduled_at' => $session->scheduled_at ? $session->scheduled_at->format('Y-m-d H:i:s') : null,
                                'time_ago' => $session->created_at->diffForHumans(),
                            ];
                        });
                    
                    // Get pending request count
                    $pendingRequestCount = TeachingSession::where('teacher_id', $teacherProfile->id)
                        ->where('status', 'requested')
                        ->count();
                } catch (Exception $e) {
                    // Log the error but continue with empty data
                    Log::error('Error fetching teaching sessions: ' . $e->getMessage());
                }
            }
            
            // Check if Message model exists
            if (class_exists('App\Models\Message') && Schema::hasTable('messages')) {
                try {
                    // Get recent messages
                    $messages = Message::where('recipient_id', $user->id)
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->with(['sender'])
                        ->get()
                        ->map(function ($message) {
                            return [
                                'id' => $message->id,
                                'sender' => [
                                    'id' => $message->sender->id,
                                    'name' => $message->sender->name,
                                    'avatar' => $message->sender->profile_photo_url ?? null,
                                    'is_online' => $message->sender->is_online ?? false,
                                ],
                                'message' => $message->content,
                                'time_ago' => $message->created_at->diffForHumans(),
                                'is_read' => (bool) $message->read_at,
                            ];
                        });
                    
                    // Get unread message count
                    $unreadMessageCount = Message::where('recipient_id', $user->id)
                        ->whereNull('read_at')
                        ->count();
                } catch (Exception $e) {
                    // Log the error but continue with empty data
                    Log::error('Error fetching messages: ' . $e->getMessage());
                }
            }
            
            // Check if User model has roles relationship
            if (method_exists($user, 'isOnline')) {
                try {
                    // Get online students
                    $onlineStudents = User::where('role', 'student')
                        ->where(function($query) {
                            $query->where('is_online', true)
                                ->orWhere(function($query) {
                                    $query->whereNotNull('last_active_at')
                                        ->where('last_active_at', '>', now()->subMinutes(5));
                                });
                        })
                        ->whereHas('teachingSessions', function ($query) use ($teacherProfile) {
                            $query->where('teacher_id', $teacherProfile->id);
                        })
                        ->take(10)
                        ->get()
                        ->map(function ($user) {
                            return [
                                'id' => $user->id,
                                'name' => $user->name,
                                'avatar' => $user->profile_photo_url ?? null,
                                'is_online' => true,
                            ];
                        });
                } catch (Exception $e) {
                    // Log the error but continue with empty data
                    Log::error('Error fetching online students: ' . $e->getMessage());
                }
            }
            
            return response()->json([
                'session_requests' => $sessionRequests,
                'messages' => $messages,
                'online_students' => $onlineStudents,
                'unread_message_count' => $unreadMessageCount,
                'pending_request_count' => $pendingRequestCount,
            ]);
        } catch (Exception $e) {
            Log::error('Error in SidebarController::getData: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'An error occurred while fetching sidebar data',
                'session_requests' => [],
                'messages' => [],
                'online_students' => [],
                'unread_message_count' => 0,
                'pending_request_count' => 0,
            ], 500);
        }
    }
    
    /**
     * Accept a session request
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptSessionRequest($id)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized',
                ], 401);
            }
            
            if ($user->role !== 'teacher') {
                return response()->json([
                    'error' => 'Unauthorized - User is not a teacher',
                ], 403);
            }
            
            $teacherProfile = $user->teacherProfile;
            
            if (!$teacherProfile) {
                return response()->json([
                    'error' => 'Teacher profile not found',
                ], 404);
            }
            
            // Check if TeachingSession model exists
            if (!class_exists('App\Models\TeachingSession') || !Schema::hasTable('teaching_sessions')) {
                return response()->json([
                    'error' => 'Teaching sessions functionality is not available',
                ], 501);
            }
            
            $session = TeachingSession::where('id', $id)
                ->where('teacher_id', $teacherProfile->id)
                ->where('status', 'requested')
                ->first();
            
            if (!$session) {
                return response()->json([
                    'error' => 'Session request not found',
                ], 404);
            }
            
            $session->status = 'accepted';
            $session->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Session request accepted',
            ]);
        } catch (Exception $e) {
            Log::error('Error in SidebarController::acceptSessionRequest: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'An error occurred while accepting the session request',
            ], 500);
        }
    }
    
    /**
     * Decline a session request
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function declineSessionRequest($id)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized',
                ], 401);
            }
            
            if ($user->role !== 'teacher') {
                return response()->json([
                    'error' => 'Unauthorized - User is not a teacher',
                ], 403);
            }
            
            $teacherProfile = $user->teacherProfile;
            
            if (!$teacherProfile) {
                return response()->json([
                    'error' => 'Teacher profile not found',
                ], 404);
            }
            
            // Check if TeachingSession model exists
            if (!class_exists('App\Models\TeachingSession') || !Schema::hasTable('teaching_sessions')) {
                return response()->json([
                    'error' => 'Teaching sessions functionality is not available',
                ], 501);
            }
            
            $session = TeachingSession::where('id', $id)
                ->where('teacher_id', $teacherProfile->id)
                ->where('status', 'requested')
                ->first();
            
            if (!$session) {
                return response()->json([
                    'error' => 'Session request not found',
                ], 404);
            }
            
            $session->status = 'declined';
            $session->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Session request declined',
            ]);
        } catch (Exception $e) {
            Log::error('Error in SidebarController::declineSessionRequest: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'An error occurred while declining the session request',
            ], 500);
        }
    }
} 