<?php

namespace App\Http\Middleware;

use App\Models\Notification;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $notificationId = $request->route('notification');
        
        if ($notificationId) {
            $notification = Notification::find($notificationId);
            
            if (!$notification) {
                return response()->json(['error' => 'Notification not found'], 404);
            }
            
            if ($notification->notifiable_type !== get_class($request->user()) || 
                $notification->notifiable_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized access to notification'], 403);
            }
        }
        
        return $next($request);
    }
}
