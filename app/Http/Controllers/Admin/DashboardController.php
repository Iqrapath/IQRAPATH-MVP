<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Subscription;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use App\Models\VerificationRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(Request $request): Response
    {
        // Get counts for dashboard stats
        $teacherCount = User::where('role', 'teacher')->count();
        $studentCount = User::where('role', 'student')->count();
        $activeSubscriptionCount = Subscription::where('status', 'active')->count();
        $pendingVerificationCount = VerificationRequest::where('status', 'pending')->count();

        // Get revenue data for the chart
        $revenueData = $this->getRevenueData();

        // Get recent students
        $recentStudents = User::where('role', 'student')
            ->with('studentProfile')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'avatar' => $student->avatar,
                    'email' => $student->email,
                    'created_at' => $student->created_at->format('Y-m-d'),
                ];
            });

        // Get recent bookings
        $recentBookings = Booking::with(['student', 'teacher', 'subject'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'booking_uuid' => $booking->booking_uuid,
                    'student_name' => $booking->student->name,
                    'student_avatar' => $booking->student->avatar,
                    'teacher_name' => $booking->teacher->name,
                    'subject_name' => $booking->subject->name,
                    'booking_date' => $booking->booking_date->format('Y-m-d'),
                    'status' => $booking->status,
                    'created_at' => $booking->created_at->format('Y-m-d'),
                ];
            });

        // Get pending teacher verifications
        $pendingVerifications = VerificationRequest::where('status', 'pending')
            ->with(['teacherProfile.user', 'teacherProfile.documents'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($verification) {
                return [
                    'id' => $verification->id,
                    'teacher_id' => $verification->teacherProfile->user->id,
                    'teacher_name' => $verification->teacherProfile->user->name,
                    'teacher_avatar' => $verification->teacherProfile->user->avatar,
                    'teacher_email' => $verification->teacherProfile->user->email,
                    'submitted_at' => $verification->submitted_at ? $verification->submitted_at->format('Y-m-d') : null,
                    'docs_status' => $verification->docs_status,
                    'document_count' => $verification->teacherProfile->documents->count(),
                ];
            });

        // Get admin notifications
        $adminNotifications = Notification::where('notifiable_id', $request->user()->id)
            ->where('notifiable_type', User::class)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        $unreadCount = $adminNotifications->whereNull('read_at')->count();

        return Inertia::render('admin/dashboard', [
            'adminProfile' => $request->user()->adminProfile,
            'stats' => [
                'teacherCount' => $teacherCount,
                'studentCount' => $studentCount,
                'activeSubscriptionCount' => $activeSubscriptionCount,
                'pendingVerificationCount' => $pendingVerificationCount,
            ],
            'revenueData' => $revenueData,
            'recentStudents' => $recentStudents,
            'recentBookings' => $recentBookings,
            'pendingVerifications' => $pendingVerifications,
            'adminNotifications' => $adminNotifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Get revenue data for the chart.
     */
    private function getRevenueData(): array
    {
        $currentYear = Carbon::now()->year;
        $months = [];
        
        // Get revenue for each month of the current year
        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::createFromDate($currentYear, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($currentYear, $month, 1)->endOfMonth();
            
            // Sum all subscription transactions for the month
            $revenue = SubscriptionTransaction::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed')
                ->sum('amount');
            
            $months[] = [
                'month' => Carbon::createFromDate($currentYear, $month, 1)->format('M'),
                'revenue' => $revenue,
            ];
        }
        
        return [
            'year' => $currentYear,
            'months' => $months,
            'currentMonthRevenue' => $months[Carbon::now()->month - 1]['revenue'],
            'totalRevenue' => array_sum(array_column($months, 'revenue')),
        ];
    }
}
