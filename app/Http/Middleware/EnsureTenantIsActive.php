<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    /**
     * Handle an incoming request.
     * Enforces account and tenant status checks on all authenticated operations.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // 1. Check user account active status
            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', 'Your account has been deactivated. Please contact support.');
            }

            // 2. Super admins are platform administrators and not bound to a tenant
            if ($user->isSuperAdmin()) {
                return $next($request);
            }

            // 3. For shop owners and staff, verify tenant status
            $tenant = $user->tenant;

            if (!$tenant) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', 'No shop account is associated with your login.');
            }

            if ($tenant->status === 'suspended') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', 'Your shop account is currently suspended. Please contact platform support.');
            }

            if ($tenant->status === 'inactive') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', 'Your shop account is currently inactive.');
            }
        }

        return $next($request);
    }
}

