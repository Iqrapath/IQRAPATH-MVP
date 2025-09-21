<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Events\UserRegistered;
use App\Services\ContentPageService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(
        private ContentPageService $contentPageService
    ) {}

    /**
     * Show the student/guardian registration page.
     */
    public function createStudentGuardian(): Response
    {
        $content = $this->contentPageService->getSignUpContent();
        
        return Inertia::render('auth/register-student-guardian', [
            'content' => $content,
        ]);
    }

    /**
     * Show the teacher registration page.
     */
    public function createTeacher(): Response
    {
        $content = $this->contentPageService->getSignUpContent();
        
        return Inertia::render('auth/register-teacher', [
            'content' => $content,
        ]);
    }

    /**
     * Legacy method - redirect to student-guardian registration
     */
    public function create(): Response
    {
        return redirect()->route('register.student-guardian');
    }

    /**
     * Handle student/guardian registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function storeStudentGuardian(Request $request): Response
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['required', 'accepted'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => null, // Will be assigned in onboarding
        ]);

        // Dispatch events
        event(new Registered($user));
        event(new UserRegistered($user));

        // Auto-login the user so email verification works properly
        Auth::login($user);

        return Inertia::render('auth/register-student-guardian', [
            'success' => true,
            'user' => $user,
        ]);
    }

    /**
     * Handle teacher registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function storeTeacher(Request $request): Response
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['required', 'accepted'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'teacher',
        ]);

        // Dispatch events
        event(new Registered($user));
        event(new UserRegistered($user));

        // Auto-login the user so email verification works properly
        Auth::login($user);

        return Inertia::render('auth/register-teacher', [
            'success' => true,
            'user' => $user,
        ]);
    }

    /**
     * Legacy registration method - redirect to student-guardian
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): Response
    {
        return $this->storeStudentGuardian($request);
    }
}
