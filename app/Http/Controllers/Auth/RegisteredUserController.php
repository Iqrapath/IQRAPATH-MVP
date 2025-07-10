<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate common fields
        $commonRules = [
            'name' => 'required|string|max:255',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['required', 'accepted'],
        ];
        
        // Determine if registration is via email or phone
        if (!empty($request->email) && empty($request->phone)) {
            // Email registration
            $validator = Validator::make($request->all(), array_merge($commonRules, [
                'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
                'phone' => 'nullable|string|max:20',
            ]));
        } elseif (!empty($request->phone) && empty($request->email)) {
            // Phone registration
            $validator = Validator::make($request->all(), array_merge($commonRules, [
                'phone' => 'required|string|max:20|unique:'.User::class.',phone',
                'email' => 'nullable|string|lowercase|email|max:255',
            ]));
        } else {
            // Both or neither provided - require email
            $validator = Validator::make($request->all(), array_merge($commonRules, [
                'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
                'phone' => 'nullable|string|max:20',
            ]));
        }
        
        $validator->validate();

        $userData = [
            'name' => $request->name,
            'password' => Hash::make($request->password),
        ];
        
        // Add email if provided
        if (!empty($request->email)) {
            $userData['email'] = $request->email;
        }
        
        // Add phone if provided
        if (!empty($request->phone)) {
            $userData['phone'] = $request->phone;
        }

        $user = User::create($userData);

        event(new Registered($user));

        Auth::login($user);

        // All new users are unassigned by default
        return redirect()->route('unassigned');
    }
}
