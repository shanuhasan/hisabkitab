<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisterController extends Controller
{
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

        $user = DB::transaction(function () use ($validated) {
            // Generate unique slug for tenant
            $baseSlug = Str::slug($validated['shop_name']);
            $uniqueSlug = $baseSlug . '-' . strtolower(Str::random(6));

            // 1. Create Tenant (Shop)
            $tenant = Tenant::create([
                'name' => $validated['shop_name'],
                'slug' => $uniqueSlug,
                'owner_name' => $validated['owner_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'currency' => 'INR',
                'status' => 'active',
                'settings' => [
                    'language' => 'hi_en',
                    'reminder_template' => 'standard',
                    'allow_negative_balance' => true,
                ],
            ]);

            // 2. Create Shop Owner User
            $owner = User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['owner_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'role' => 'shop_owner',
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);

            // 3. Attach 30-Day Starter Trial Subscription
            $starterPlan = Plan::where('slug', 'trial')->first() ?? Plan::firstOrCreate(
                ['slug' => 'trial'],
                [
                    'name' => 'Free 30-Day Trial',
                    'price' => 0.00,
                    'duration_days' => 30,
                    'customer_limit' => 100,
                    'features' => [
                        'pdf_statements' => true,
                        'whatsapp_reminders' => true,
                        'csv_exports' => true,
                        'advanced_reports' => true,
                    ],
                    'description' => 'Full-featured 30-day free trial for new shopkeepers.',
                    'is_active' => true,
                ]
            );

            Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $starterPlan->id,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDays(30)->toDateString(),
                'trial_ends_at' => now()->addDays(30)->toDateString(),
                'status' => 'trial',
                'payment_details' => ['type' => 'free_trial_activation'],
            ]);

            return $owner;
        });

        // Log the newly registered shop owner in
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Swagat hai! Aapka UdhaarHisab digital khata ban gaya hai.');
    }
}

