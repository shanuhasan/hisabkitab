<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\ShopResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ShopRegistrationService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected ShopRegistrationService $registrationService,
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * POST /api/v1/auth/register — create a new shop + owner and return a login token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->registrationService->register($request->validated());

        return response()->json(
            $this->sessionPayload($user, $request->input('device_name', 'mobile-app'), 'Swagat hai! Aapka UdhaarHisab digital khata ban gaya hai.'),
            201
        );
    }

    /**
     * POST /api/v1/auth/login — log in with mobile number or email and get a token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ], [
            'login.required' => 'Please enter your registered mobile number or email address.',
            'password.required' => 'Please enter your password.',
        ]);

        $loginInput = trim($validated['login']);
        $throttleKey = 'api-login:' . Str::lower($loginInput) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'login' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->status(429);
        }

        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);
        $user = $isEmail
            ? User::where('email', $loginInput)->first()
            : User::where('phone', preg_replace('/[^0-9]/', '', $loginInput))->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'login' => 'Invalid mobile/email or password entered.',
            ]);
        }

        $blockReason = match (true) {
            !$user->is_active => 'Your user account has been deactivated. Please contact support.',
            $user->isSuperAdmin() => 'The mobile app is for shop owners and staff. Please use the Admin Panel on the website.',
            !$user->tenant => 'No shop is linked to this account.',
            $user->tenant->status === 'suspended' => 'Your shop account is currently suspended. Please contact platform support.',
            $user->tenant->status === 'inactive' => 'Your shop account is currently inactive.',
            default => null,
        };

        if ($blockReason) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages(['login' => $blockReason]);
        }

        RateLimiter::clear($throttleKey);

        return response()->json(
            $this->sessionPayload($user, $validated['device_name'] ?? 'mobile-app', 'Login successful.')
        );
    }

    /**
     * GET /api/v1/auth/me — the logged-in user, their shop and plan status.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => new UserResource($user),
            'shop' => new ShopResource($user->tenant),
            'subscription' => $this->subscriptionSummary($user),
        ]);
    }

    /**
     * POST /api/v1/auth/logout — revoke the token used for this request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    protected function sessionPayload(User $user, string $deviceName, string $message): array
    {
        $token = $user->createToken($deviceName)->plainTextToken;

        return [
            'message' => $message,
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
            'shop' => new ShopResource($user->tenant),
            'subscription' => $this->subscriptionSummary($user),
        ];
    }

    protected function subscriptionSummary(User $user): ?array
    {
        if (!$user->tenant) {
            return null;
        }

        $status = $this->subscriptionService->getSubscriptionStatus($user->tenant);

        return [
            'plan' => $status['plan']->name,
            'status' => $status['status'],
            'is_valid' => $status['is_valid'],
            'is_expired' => $status['is_expired'],
            'is_near_expiry' => $status['is_near_expiry'],
            'days_remaining' => $status['days_remaining'],
            'customer_count' => $status['customer_count'],
            'customer_limit' => $status['customer_limit'],
        ];
    }
}
