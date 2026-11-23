<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\ShopRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(protected ShopRegistrationService $registrationService) {}

    /**
     * Show shop owner registration form.
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * Handle incoming registration request.
     * Atomically creates Tenant, Owner User, and initial Free Trial Subscription.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = $this->registrationService->register($validated);

        // Log the newly registered shop owner in
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Swagat hai! Aapka UdhaarHisab digital khata ban gaya hai.');
    }
}

