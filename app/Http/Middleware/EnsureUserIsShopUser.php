<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsShopUser
{
    /**
     * Block platform super admins from shop (tenant) pages.
     *
     * Super admins don't own a shop, so any shop page sends them back
     * to the platform admin panel.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isSuperAdmin()) {
            if ($request->expectsJson()) {
                abort(403, 'Shop pages are not available to super admins.');
            }

            return redirect()
                ->route('admin.dashboard')
                ->with('warning', 'Shop pages are only for shop owners and staff. You have been redirected to the Admin Panel.');
        }

        return $next($request);
    }
}
