<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\TeacherProfile;
use App\Models\TeachingSession;
use App\Models\Dispute;
use App\Models\VerificationRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class UrgentActionsController extends Controller
{
    /**
     * Get counts of items requiring urgent action
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Cache results for 5 minutes to improve performance
        $urgentActions = Cache::remember('admin_urgent_actions', 300, function () {
            return [
                'withdrawalRequests' => PayoutRequest::where('status', 'pending')->count(),
                'teacherApplications' => VerificationRequest::where('status', 'pending')->count(),
                'pendingSessions' => TeachingSession::whereNull('teacher_id')
                    ->orWhere('status', 'pending_teacher')
                    ->count(),
                'reportedDisputes' => Dispute::where('status', 'reported')->count(),
            ];
        });
        
        return response()->json($urgentActions);
    }
} 