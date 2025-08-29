<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTeacherVerification
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Only apply verification check to teachers
        if ($user && $user->role === 'teacher') {
            $teacherProfile = $user->teacherProfile;
            
            // If no teacher profile or not verified, redirect to onboarding success screen
            if (!$teacherProfile || !$teacherProfile->verified) {
                return redirect()->route('onboarding.teacher')->with('verification_required', true);
            }
        }
        
        return $next($request);
    }
}
