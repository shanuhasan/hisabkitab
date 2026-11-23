<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    /**
     * Show main shop dashboard with real-time statistics, charts, and recent activity.
     */
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        // Super admins don't own a shop; send them to the platform admin panel.
        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $tenant = $user->tenant;

        $dashboardData = $tenant
            ? $this->reportService->getDashboardData($tenant->id)
            : [
                'metrics' => [
                    'total_customers' => 0,
                    'total_udhaar_given' => '0.00',
                    'total_payments_received' => '0.00',
                    'total_outstanding_balance' => '0.00',
                    'total_advance_balance' => '0.00',
                    'today_udhaar' => '0.00',
                    'today_collection' => '0.00',
                ],
                'recent_transactions' => collect(),
                'highest_outstanding_customers' => collect(),
                'recent_payments' => collect(),
                'daily_chart' => ['labels' => [], 'data' => []],
                'monthly_chart' => ['labels' => [], 'credit' => [], 'payment' => []],
            ];

        return view('dashboard', compact('tenant', 'user', 'dashboardData'));
    }
}
