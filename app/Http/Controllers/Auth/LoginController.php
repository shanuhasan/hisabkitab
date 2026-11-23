<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle incoming login request with mobile or email.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $loginInput = trim($validated['login']);

        // Rate limiting key based on IP and login credential
        $throttleKey = Str::transliterate(Str::lower($loginInput) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'login' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        // Determine if login is email or mobile number
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);
        $cleanPhone = preg_replace('/[^0-9]/', '', $loginInput);

        $user = $isEmail
            ? User::where('email', $loginInput)->first()
            : User::where('phone', $cleanPhone)->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'login' => 'Invalid mobile/email or password entered.',
            ]);
        }

        // 1. Check if user is active
        if (!$user->is_active) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'login' => 'Your user account has been deactivated. Please contact support.',
            ]);
        }

        // 2. Check if shop/tenant is active
        if ($user->tenant) {
            if ($user->tenant->status === 'suspended') {
                RateLimiter::hit($throttleKey);
                throw ValidationException::withMessages([
                    'login' => 'Your shop account is currently suspended. Please contact platform support.',
                ]);
            }

            if ($user->tenant->status === 'inactive') {
                RateLimiter::hit($throttleKey);
                throw ValidationException::withMessages([
                    'login' => 'Your shop account is currently inactive.',
                ]);
            }
        }

        // Authentication successful
        RateLimiter::clear($throttleKey);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($user->isSuperAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'You have been logged out successfully.');
    }
}

