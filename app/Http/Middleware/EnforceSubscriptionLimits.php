<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceSubscriptionLimits
{
    public function __construct(protected SubscriptionService $subscriptionService) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $type = null, ?string $feature = null): Response
    {
        $user = auth()->user();

        // Skip check for super admins or unauthenticated requests
        if (!$user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $tenant = $user->tenant;
        if (!$tenant) {
            return $next($request);
        }

        $status = $this->subscriptionService->getSubscriptionStatus($tenant);

        // 1. If subscription is expired (and not in grace period), block access to business operations
        if ($status['is_expired']) {
            if (!$request->routeIs('subscription.*') && !$request->routeIs('logout') && !$request->routeIs('shop.profile*')) {
                return redirect()->route('subscription.index')
                    ->with('error', 'Aapka subscription samapt ho gaya hai. Digital khata chalu rakhne ke liye kripya naya plan sunein.');
            }
        }

        // 2. Feature-specific restriction check (e.g. 'feature', 'csv_exports')
        if ($type === 'feature' && $feature) {
            if (!$this->subscriptionService->canAccessFeature($tenant, $feature)) {
                $featureName = ucfirst(str_replace('_', ' ', $feature));
                if ($request->wantsJson()) {
                    return response()->json([
                        'error' => "This feature ({$featureName}) is not included in your current {$status['plan']->name}. Please upgrade your plan.",
                    ], 403);
                }

                return redirect()->route('subscription.index')
                    ->with('warning', "{$featureName} feature keval upgrade plan mein uplabdh hai. Kripya apna plan upgrade karein.");
            }
        }

        return $next($request);
    }
}

