<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Teacher\SubjectController;
use App\Http\Controllers\Teacher\AvailabilityController;
use App\Http\Controllers\Teacher\DashboardController;
use App\Http\Controllers\Teacher\FinancialController;
use App\Http\Controllers\Teacher\ProfileController;
use App\Http\Controllers\Teacher\TeacherReviewController;
use App\Http\Controllers\Teacher\BookingController;
use App\Http\Controllers\Teacher\SidebarController;
use App\Http\Controllers\Teacher\RecommendedStudentsController;
use App\Http\Controllers\Teacher\SessionsController;
use App\Http\Controllers\Teacher\RequestsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Teacher Routes
|--------------------------------------------------------------------------
|
| Here is where you can register teacher specific routes for your application.
| These routes handle teacher dashboard, profile management, subjects,
| availabilities, documents, and financial information.
|
*/

Route::middleware(['auth', 'verified', 'role:teacher', 'teacher.verified'])->prefix('teacher')->name('teacher.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Onboarding redirect
    Route::get('/onboarding', function () {
        return redirect()->route('onboarding.teacher');
    })->name('onboarding');
    
    // Schedule
    Route::get('/schedule', function () {
        return Inertia::render('teacher/schedule/index');
    })->name('schedule');
    
    // Sessions
    Route::get('/sessions', [SessionsController::class, 'index'])->name('sessions');
    Route::get('/sessions/upcoming', [SessionsController::class, 'getUpcomingSessions'])->name('sessions.upcoming');
    Route::get('/sessions/past', [SessionsController::class, 'getPastSessions'])->name('sessions.past');
    Route::post('/sessions/{sessionId}/join', [SessionsController::class, 'joinSession'])->name('sessions.join');
    Route::post('/sessions/requests/{booking}/accept', [SessionsController::class, 'acceptRequest'])->name('sessions.requests.accept');
    Route::post('/sessions/requests/{booking}/decline', [SessionsController::class, 'declineRequest'])->name('sessions.requests.decline');
    Route::get('/sessions/student/{studentId}/profile', [SessionsController::class, 'getStudentProfile'])->name('sessions.student.profile');
    
    // Students
    Route::get('/students', function () {
        return Inertia::render('teacher/students/index');
    })->name('students');
    
    // Requests
    Route::get('/requests', [RequestsController::class, 'index'])->name('requests');
    Route::post('/requests/{request}/accept', [RequestsController::class, 'accept'])->name('requests.accept');
    Route::post('/requests/{request}/decline', [RequestsController::class, 'decline'])->name('requests.decline');
    
    // Availability
    Route::get('/availability/{teacherId}', [AvailabilityController::class, 'getAvailability'])->name('availability.get');
    Route::post('/availability/{teacherId}', [AvailabilityController::class, 'updateAvailability'])->name('availability.update');
    
    // Debug route for recommended students
    Route::get('/debug-recommended', function () {
        try {
            $teacher = auth()->user();
            $teacherId = $teacher->id;
            $teacherProfile = $teacher->teacherProfile;
            
            return response()->json([
                'teacher_id' => $teacherId,
                'has_teacher_profile' => $teacherProfile ? true : false,
                'teacher_profile_id' => $teacherProfile ? $teacherProfile->id : null,
                'subjects_count' => \App\Models\Subject::count(),
                'subject_templates_count' => \App\Models\SubjectTemplates::count(),
                'bookings_count' => \App\Models\Booking::count(),
                'teacher_subjects' => $teacherProfile ? \App\Models\Subject::where('teacher_profile_id', $teacherProfile->id)->count() : 0
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    })->name('debug.recommended');
    
    // Notifications
    Route::get('/notifications', function () {
        return Inertia::render('teacher/notifications/notifications');
    })->name('notifications');
    
    // Subject routes
    Route::resource('subjects', SubjectController::class);
    
    // Document management routes - API-like endpoints
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    
    // Financial routes
    Route::get('/earnings', [FinancialController::class, 'index'])->name('earnings');
    Route::get('/earnings/history', [FinancialController::class, 'history'])->name('earnings.history');
    Route::get('/earnings/payouts', [FinancialController::class, 'payouts'])->name('earnings.payouts');
    Route::post('/earnings/request-payout', [FinancialController::class, 'requestPayout'])->name('earnings.request-payout');
    Route::post('/earnings/settings', [FinancialController::class, 'saveSettings'])->name('earnings.save-settings');
    Route::post('/earnings/sync-wallet', [FinancialController::class, 'syncWallet'])->name('earnings.sync-wallet');
    
    // Teacher profile routes
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile/basic-info', [ProfileController::class, 'updateBasicInfo'])->name('profile.update-basic-info');
    Route::put('/profile/teacher-info', [ProfileController::class, 'updateProfile'])->name('profile.update-teacher-info');
    Route::put('/profile/subjects', [ProfileController::class, 'updateSubjects'])->name('profile.update-subjects');
    Route::put('/profile/availability', [ProfileController::class, 'updateAvailability'])->name('profile.update-availability');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.update-avatar');
    Route::post('/profile/intro-video', [ProfileController::class, 'uploadIntroVideo'])->name('profile.upload-intro-video');
    Route::get('/profile/intro-video', function () {
        return Inertia::render('teacher/profile/intro-video');
    })->name('profile.intro-video');
    
    // Teacher subjects management
    Route::post('/profile/subjects', [SubjectController::class, 'store'])->name('profile.subjects.store');
    Route::put('/profile/subjects/{subject}', [SubjectController::class, 'update'])->name('profile.subjects.update');
    Route::delete('/profile/subjects/{subject}', [SubjectController::class, 'destroy'])->name('profile.subjects.destroy');
    
    // Teacher availability management
    Route::post('/profile/availabilities', [AvailabilityController::class, 'store'])->name('profile.availabilities.store');
    Route::put('/profile/availabilities/{availability}', [AvailabilityController::class, 'update'])->name('profile.availabilities.update');
    Route::delete('/profile/availabilities/{availability}', [AvailabilityController::class, 'destroy'])->name('profile.availabilities.destroy');
    Route::put('/profile/availability-preferences', [AvailabilityController::class, 'updatePreferences'])->name('profile.availability-preferences.update');
    
    // Teaching sessions - API routes only (page routes removed to avoid conflicts)
        // Teaching sessions
        // Route::get('/sessions/upcoming', function () {
        //     return Inertia::render('teacher/sessions/upcoming');
        // })->name('sessions.upcoming');
        // Route::get('/sessions/past', function () {
        //     return Inertia::render('teacher/sessions/past');
        // })->name('sessions.past');
    
    // Student requests - handled by RequestsController above

    // Booking management
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('/bookings/{booking}/approve', [BookingController::class, 'approve'])->name('bookings.approve');
    Route::post('/bookings/{booking}/reject', [BookingController::class, 'reject'])->name('bookings.reject');
    // Route::post('/bookings/{booking}/reschedule', [BookingController::class, 'reschedule'])->name('bookings.reschedule');
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');

    // Teacher reviews (students and guardians can submit)
    Route::post('/reviews', [TeacherReviewController::class, 'store'])->middleware('role:student,guardian')->name('reviews.store');
    Route::get('/{teacher}/reviews', [TeacherReviewController::class, 'index'])->name('reviews.index');
    
    // Sidebar data API
    Route::get('/sidebar-data', [SidebarController::class, 'getSidebarData'])->name('sidebar.data');
    Route::post('/requests/{booking}/accept', [SidebarController::class, 'acceptRequest'])->name('requests.accept');
    Route::post('/requests/{booking}/decline', [SidebarController::class, 'declineRequest'])->name('requests.decline');
    
    // Recommended students API
    Route::get('/recommended-students', [RecommendedStudentsController::class, 'getRecommendedStudents'])->name('recommended-students');
    
    // Payment Methods
    Route::get('/payment-methods', [App\Http\Controllers\Teacher\PaymentMethodController::class, 'index'])->name('payment-methods.index');
    Route::post('/payment-methods', [App\Http\Controllers\Teacher\PaymentMethodController::class, 'store'])->name('payment-methods.store');
    Route::patch('/payment-methods/{paymentMethod}', [App\Http\Controllers\Teacher\PaymentMethodController::class, 'update'])->name('payment-methods.update');
    Route::delete('/payment-methods/{paymentMethod}', [App\Http\Controllers\Teacher\PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');
    Route::patch('/payment-methods/{paymentMethod}/set-default', [App\Http\Controllers\Teacher\PaymentMethodController::class, 'setDefault'])->name('payment-methods.set-default');
    Route::post('/payment-methods/{paymentMethod}/verify', [App\Http\Controllers\Teacher\PaymentMethodController::class, 'verify'])->name('payment-methods.verify');
    
    // Banks list for payment methods
    Route::get('/banks', [App\Http\Controllers\Teacher\PaymentMethodController::class, 'getBanks'])->name('banks');
    
    // Debug route to test bank fetching
    Route::get('/debug-banks', function () {
        $service = app(\App\Services\BankVerificationService::class);
        $banks = $service->getBankList('NG');
        return response()->json([
            'count' => count($banks),
            'sample' => array_slice($banks, 0, 5),
            'all' => $banks
        ]);
    })->name('debug.banks');
    
    // Debug route to check payment methods
    Route::get('/debug-payment-methods', function () {
        $user = auth()->user();
        $methods = $user->paymentMethods()->latest()->get();
        return response()->json([
            'count' => $methods->count(),
            'methods' => $methods->toArray()
        ]);
    })->name('debug.payment-methods');
}); 