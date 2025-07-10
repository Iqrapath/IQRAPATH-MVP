<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Only update if last activity was more than 1 minute ago
            // This prevents database writes on every single request
            $lastActive = $user->last_active_at ? Carbon::parse($user->last_active_at) : null;
            
            if (!$lastActive || $lastActive->diffInMinutes(now()) >= 1) {
                // Update last_active_at directly in the database
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'last_active_at' => Carbon::now(),
                    ]);
                
                // Update status based on activity if user is online or offline
                if ($user->status_type === 'online' || $user->status_type === 'offline' || $user->status_type === 'away') {
                    $this->updateActivityStatus($user);
                }
            }
        }
        
        return $next($request);
    }
    
    /**
     * Update user's status based on their activity.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    protected function updateActivityStatus($user): void
    {
        $lastActive = $user->last_active_at ? Carbon::parse($user->last_active_at) : null;
        
        if (!$lastActive) {
            $status = 'offline';
        } elseif ($lastActive->gt(Carbon::now()->subMinutes(5))) {
            $status = 'online';
        } elseif ($lastActive->gt(Carbon::now()->subMinutes(30))) {
            $status = 'away';
        } else {
            $status = 'offline';
        }
        
        // Only update if status changed
        if ($user->status_type !== $status) {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'status_type' => $status,
                ]);
        }
    }
}
