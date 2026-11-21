<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    /**
     * Show main dashboard overview.
     */
    public function index(): View
    {
        $user = auth()->user();
        $tenant = $user->tenant;

        $metrics = $tenant
            ? $this->reportService->getShopOverviewMetrics($tenant->id)
            : [
                'total_udhaar_receivable' => '0.00',
                'total_advance_payable' => '0.00',
                'total_customers' => 0,
                'today_credit' => '0.00',
                'today_payment' => '0.00',
            ];

        return view('dashboard', compact('tenant', 'user', 'metrics'));
    }
}

