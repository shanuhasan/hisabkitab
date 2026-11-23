<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the mobile API: only active shop owners/staff of an active shop may use it.
 * Always answers with JSON so the mobile app can show the message directly.
 */
class EnsureApiShopAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->deny('Please log in again.', 401, 'unauthenticated');
        }

        if (!$user->is_active) {
            $user->currentAccessToken()?->delete();

            return $this->deny('Your account has been deactivated. Please contact support.', 403, 'account_inactive');
        }

        if ($user->isSuperAdmin()) {
            return $this->deny('The mobile app is for shop owners and staff. Please use the Admin Panel on the website.', 403, 'super_admin_not_allowed');
        }

        $tenant = $user->tenant;

        if (!$tenant) {
            return $this->deny('No shop is linked to this account.', 403, 'no_shop');
        }

        if ($tenant->status === 'suspended') {
            return $this->deny('Your shop account is currently suspended. Please contact platform support.', 403, 'shop_suspended');
        }

        if ($tenant->status === 'inactive') {
            return $this->deny('Your shop account is currently inactive.', 403, 'shop_inactive');
        }

        return $next($request);
    }

    protected function deny(string $message, int $status, string $code): Response
    {
        return response()->json(['message' => $message, 'code' => $code], $status);
    }
}
