<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UrgentAction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UrgentActionController extends Controller
{
    /**
     * Get urgent actions for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Cache key includes user role for permission-based caching
        $cacheKey = "urgent_actions_user_{$user->id}_role_{$user->role}";
        
        $urgentActions = Cache::remember($cacheKey, 120, function () use ($user) {
            return UrgentAction::getForUser($user)
                ->filter(function ($action) {
                    return $action->isUrgent();
                })
                ->map(function ($action) {
                    return [
                        'id' => $action->id,
                        'count' => $action->cached_count,
                        'title' => $action->title,
                        'actionText' => $action->action_text,
                        'actionUrl' => $action->action_url,
                        'priority' => $action->priority_level,
                        'lastUpdated' => $action->last_updated?->toISOString(),
                    ];
                })
                ->values();
        });

        return response()->json([
            'success' => true,
            'data' => $urgentActions,
            'total' => $urgentActions->count(),
            'cached_at' => now()->toISOString(),
        ]);
    }

    /**
     * Force refresh urgent action counts (admin only)
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user || !in_array($user->role, ['super-admin', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Clear all user caches
        Cache::forget("urgent_actions_user_*");
        
        // Update all active urgent actions
        $urgentActions = UrgentAction::where('is_active', true)->get();
        
        foreach ($urgentActions as $action) {
            $action->updateCachedCount();
        }

        return response()->json([
            'success' => true,
            'message' => 'Urgent action counts refreshed successfully',
            'updated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get urgent action statistics (admin only)
     */
    public function stats(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user || !in_array($user->role, ['super-admin', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = Cache::remember('urgent_actions_stats', 300, function () {
            $total = UrgentAction::where('is_active', true)->count();
            $urgent = UrgentAction::where('is_active', true)
                ->get()
                ->filter(function ($action) {
                    return $action->isUrgent();
                })
                ->count();
            
            $byPriority = UrgentAction::where('is_active', true)
                ->get()
                ->groupBy('priority_level')
                ->map(function ($group) {
                    return $group->filter(function ($action) {
                        return $action->isUrgent();
                    })->count();
                });

            return [
                'total_actions' => $total,
                'urgent_actions' => $urgent,
                'by_priority' => $byPriority,
                'last_updated' => UrgentAction::max('last_updated')?->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
