<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopManagementController extends Controller
{
    /**
     * Display directory of all registered tenant shops with search and filters.
     */
    public function index(Request $request): View
    {
        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'all');

        $query = Tenant::with(['subscriptions.plan', 'users']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('owner_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('status', 'active');
        } elseif ($status === 'suspended') {
            $query->where('status', 'suspended');
        }

        $shops = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('admin.shops.index', compact('shops', 'search', 'status'));
    }

    /**
     * Display full details, owner info, and subscription timeline for a single shop.
     */
    public function show(Tenant $shop): View
    {
        $shop->load(['users', 'subscriptions.plan', 'subscriptions.payments']);

        $customerCount = $shop->customers()->count();
        $transactionCount = $shop->transactions()->count();
        $currentSubscription = $shop->currentSubscription;

        return view('admin.shops.show', compact('shop', 'customerCount', 'transactionCount', 'currentSubscription'));
    }

    /**
     * Toggle shop status between active and suspended.
     */
    public function toggleStatus(Request $request, Tenant $shop): RedirectResponse
    {
        $newStatus = $shop->status === 'active' ? 'suspended' : 'active';
        $reason = $request->input('reason', 'Administrative action by Super Admin');

        $shop->status = $newStatus;
        $shop->save();

        AdminActivityLog::log(
            action: 'shop_status_updated',
            description: "Shop '{$shop->name}' (ID: {$shop->id}) status updated to {$newStatus}. Reason: {$reason}",
            targetType: 'Tenant',
            targetId: $shop->id
        );

        $actionWord = $newStatus === 'active' ? 'activated' : 'suspended';

        return redirect()->back()
            ->with('success', "Shop '{$shop->name}' has been successfully {$actionWord}.");
    }
}

