<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Display Super Admin Global SaaS Dashboard.
     */
    public function index(): View
    {
        // 1. Core Platform Metrics
        $totalShops = Tenant::count();
        $activeShops = Tenant::where('status', 'active')->count();
        $suspendedShops = Tenant::where('status', 'suspended')->count();

        // 2. Subscription & Subscriber Metrics
        $totalSubscribers = Subscription::where('status', 'active')
            ->distinct('tenant_id')
            ->count('tenant_id');

        $activeSubscriptions = Subscription::where('status', 'active')->count();

        // 3. Monthly Recurring Revenue (MRR)
        // Sum of monthly price for all active paid subscriptions
        $mrr = Subscription::where('status', 'active')
            ->whereHas('plan', fn($q) => $q->where('price', '>', 0))
            ->with('plan')
            ->get()
            ->sum(fn($sub) => (float) $sub->plan->price);

        // 4. Payment Statistics
        $totalRevenue = SubscriptionPayment::where('status', 'successful')->sum('amount');
        $successfulPaymentsCount = SubscriptionPayment::where('status', 'successful')->count();
        $pendingPaymentsCount = SubscriptionPayment::where('status', 'pending')->count();
        $failedPaymentsCount = SubscriptionPayment::where('status', 'failed')->count();

        // 5. Recent Feeds
        $recentShops = Tenant::with('users')->orderBy('id', 'desc')->take(6)->get();
        $recentPayments = SubscriptionPayment::with(['tenant', 'plan'])->orderBy('id', 'desc')->take(6)->get();
        $recentActivities = AdminActivityLog::with('user')->orderBy('id', 'desc')->take(6)->get();

        return view('admin.dashboard', compact(
            'totalShops',
            'activeShops',
            'suspendedShops',
            'totalSubscribers',
            'activeSubscriptions',
            'mrr',
            'totalRevenue',
            'successfulPaymentsCount',
            'pendingPaymentsCount',
            'failedPaymentsCount',
            'recentShops',
            'recentPayments',
            'recentActivities'
        ));
    }
}

