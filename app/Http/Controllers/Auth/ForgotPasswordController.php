<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    /**
     * Show the password reset request form.
     */
    public function showLinkRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Generate password reset token.
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $throttleKey = 'password-reset:' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'login' => "Too many password reset attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        $request->validate([
            'login' => ['required', 'string'],
        ], [
            'login.required' => 'Please enter your registered mobile number or email.',
        ]);

        $loginInput = trim($request->input('login'));
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);
        $cleanPhone = preg_replace('/[^0-9]/', '', $loginInput);

        $user = $isEmail
            ? User::where('email', $loginInput)->first()
            : User::where('phone', $cleanPhone)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'login' => 'We could not find an account with that mobile number or email.',
            ]);
        }

        $token = Str::random(60);
        $identifier = $user->email ?? $user->phone;

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $identifier],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // In development / local environment, redirect directly to reset screen with token
        return redirect()->route('password.reset', ['token' => $token, 'identifier' => $identifier])
            ->with('status', 'Password reset code generated. You may now choose your new password.');
    }

    /**
     * Show new password form.
     */
    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'identifier' => $request->query('identifier'),
        ]);
    }

    /**
     * Update user's password.
     */
    public function reset(Request $request): RedirectResponse
    {
        $throttleKey = 'password-reset-attempt:' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'identifier' => "Too many attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        $request->validate([
            'token' => ['required'],
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->identifier)
            ->first();

        if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
            throw ValidationException::withMessages([
                'identifier' => 'This password reset link is invalid or has expired.',
            ]);
        }

        // Check if token is older than 60 minutes
        if (now()->diffInMinutes($resetRecord->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->identifier)->delete();
            throw ValidationException::withMessages([
                'identifier' => 'This password reset link has expired. Please request a new one.',
            ]);
        }

        $user = User::where('email', $request->identifier)
            ->orWhere('phone', $request->identifier)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'identifier' => 'User not found.',
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->setRememberToken(Str::random(60));
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->identifier)->delete();

        return redirect()->route('login')->with('success', 'Your password has been reset successfully. Please log in.');
    }
}

