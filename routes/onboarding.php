<?php

use App\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Onboarding Routes
|--------------------------------------------------------------------------
|
| These routes handle the onboarding process for newly registered users.
| They guide users through role selection and initial setup.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // General onboarding (for students/guardians to choose role)
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'store']);
    
    // Role-specific onboarding
    Route::get('/onboarding/teacher', [OnboardingController::class, 'teacher'])->name('onboarding.teacher');
    Route::post('/onboarding/teacher/step', [OnboardingController::class, 'saveTeacherStep'])->name('onboarding.teacher.step');
    Route::post('/onboarding/teacher', [OnboardingController::class, 'storeTeacher'])->name('onboarding.teacher.store');
    
    Route::get('/onboarding/student', [OnboardingController::class, 'student'])->name('onboarding.student');
    Route::post('/onboarding/student', [OnboardingController::class, 'storeStudent']);
    
    Route::get('/onboarding/guardian', [OnboardingController::class, 'guardian'])->name('onboarding.guardian');
    Route::post('/onboarding/guardian', [OnboardingController::class, 'storeGuardian']);
});
