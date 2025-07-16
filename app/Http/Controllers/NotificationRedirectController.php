<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationRedirectController extends Controller
{
    /**
     * Redirect to the appropriate role-specific notification page.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect($id)
    {
        $user = Auth::user();
        
        // Verify that this notification belongs to the current user
        $notification = NotificationRecipient::where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$notification) {
            return redirect()->route('dashboard')->with('error', 'Notification not found');
        }
        
        // Redirect based on user role
        return match($user->role) {
            'teacher' => redirect()->route('teacher.notification.show', $id),
            'student' => redirect()->route('student.notification.show', $id),
            'guardian' => redirect()->route('guardian.notification.show', $id),
            'admin', 'super-admin' => redirect()->route('admin.notification.show', $id),
            default => redirect()->route('dashboard'),
        };
    }
} 