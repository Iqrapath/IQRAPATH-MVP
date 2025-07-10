<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectBasedOnRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If the user is authenticated
        if ($request->user()) {
            // Check if the route is the default dashboard
            if ($request->routeIs('dashboard')) {
                // Redirect based on role
                if ($request->user()->isUnassigned()) {
                    return redirect()->route('unassigned');
                }

                return match ($request->user()->role) {
                    'super-admin' => redirect()->route('admin.dashboard'),
                    'teacher' => redirect()->route('teacher.dashboard'),
                    'student' => redirect()->route('student.dashboard'),
                    'guardian' => redirect()->route('guardian.dashboard'),
                    default => $next($request),
                };
            }
        }

        return $next($request);
    }
}
