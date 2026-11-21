<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get aggregated financial KPIs and chart data for the Shop Dashboard.
     */
    public function getDashboardData(int $tenantId): array
    {
        $today = Carbon::today()->toDateString();

        // 1. Core KPIs
        $totalCustomers = Customer::where('tenant_id', $tenantId)->count();

        $totalUdhaarGiven = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'credit')
            ->where('is_reversed', false)
            ->sum('amount');

        $totalPaymentsReceived = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'payment')
            ->where('is_reversed', false)
            ->sum('amount');

        $totalOutstanding = Customer::where('tenant_id', $tenantId)
            ->where('current_balance', '>', 0)
            ->sum('current_balance');

        $totalAdvance = Customer::where('tenant_id', $tenantId)
            ->where('current_balance', '<', 0)
            ->sum('current_balance');

        $todayUdhaar = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'credit')
            ->where('is_reversed', false)
            ->whereDate('transaction_date', $today)
            ->sum('amount');

        $todayCollection = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'payment')
            ->where('is_reversed', false)
            ->whereDate('transaction_date', $today)
            ->sum('amount');

        // 2. Sections: Recent Transactions (Eager load customer to avoid N+1)
        $recentTransactions = Transaction::with(['customer', 'user'])
            ->where('tenant_id', $tenantId)
            ->orderBy('id', 'desc')
            ->take(8)
            ->get();

        // 3. Customers with Highest Outstanding (Top Defaulters / Borrowers)
        $highestOutstandingCustomers = Customer::where('tenant_id', $tenantId)
            ->where('current_balance', '>', 0)
            ->orderBy('current_balance', 'desc')
            ->take(5)
            ->get();

        // 4. Recent Payments
        $recentPayments = Transaction::with(['customer', 'user'])
            ->where('tenant_id', $tenantId)
            ->where('type', 'payment')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        // 5. Daily Collection Chart (Last 14 days)
        $dailyChart = $this->buildDailyCollectionChart($tenantId, 14);

        // 6. Monthly Udhaar vs Payment Chart (Last 6 months)
        $monthlyChart = $this->buildMonthlyComparisonChart($tenantId, 6);

        return [
            'metrics' => [
                'total_customers' => $totalCustomers,
                'total_udhaar_given' => number_format((float) $totalUdhaarGiven, 2, '.', ''),
                'total_payments_received' => number_format((float) $totalPaymentsReceived, 2, '.', ''),
                'total_outstanding_balance' => number_format((float) $totalOutstanding, 2, '.', ''),
                'total_advance_balance' => number_format(abs((float) $totalAdvance), 2, '.', ''),
                'today_udhaar' => number_format((float) $todayUdhaar, 2, '.', ''),
                'today_collection' => number_format((float) $todayCollection, 2, '.', ''),
            ],
            'recent_transactions' => $recentTransactions,
            'highest_outstanding_customers' => $highestOutstandingCustomers,
            'recent_payments' => $recentPayments,
            'daily_chart' => $dailyChart,
            'monthly_chart' => $monthlyChart,
        ];
    }

    /**
     * Build daily collection data for Chart.js.
     */
    protected function buildDailyCollectionChart(int $tenantId, int $days = 14): array
    {
        $startDate = Carbon::today()->subDays($days - 1);
        $endDate = Carbon::today();

        $collections = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'payment')
            ->where('is_reversed', false)
            ->whereBetween('transaction_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('transaction_date, SUM(amount) as total')
            ->groupBy('transaction_date')
            ->pluck('total', 'transaction_date')
            ->toArray();

        $labels = [];
        $data = [];

        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::today()->subDays($days - 1 - $i)->toDateString();
            $label = Carbon::parse($date)->format('d M');
            $labels[] = $label;
            $data[] = isset($collections[$date]) ? (float) $collections[$date] : 0.0;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Build monthly Udhaar vs Payment comparison chart for Chart.js.
     */
    protected function buildMonthlyComparisonChart(int $tenantId, int $months = 6): array
    {
        $labels = [];
        $creditData = [];
        $paymentData = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthDate = Carbon::today()->startOfMonth()->subMonths($i);
            $monthStart = $monthDate->toDateString();
            $monthEnd = $monthDate->copy()->endOfMonth()->toDateString();
            $labels[] = $monthDate->format('M Y');

            $monthCredit = Transaction::where('tenant_id', $tenantId)
                ->where('type', 'credit')
                ->where('is_reversed', false)
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $monthPayment = Transaction::where('tenant_id', $tenantId)
                ->where('type', 'payment')
                ->where('is_reversed', false)
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $creditData[] = (float) $monthCredit;
            $paymentData[] = (float) $monthPayment;
        }

        return [
            'labels' => $labels,
            'credit' => $creditData,
            'payment' => $paymentData,
        ];
    }

    /**
     * Get Period Report (Daily, Weekly, Monthly, or Custom Date Range).
     */
    public function getPeriodReport(int $tenantId, ?string $fromDate, ?string $toDate): array
    {
        $query = Transaction::with(['customer', 'user'])
            ->where('tenant_id', $tenantId)
            ->where('is_reversed', false)
            ->where('type', '!=', 'reversal');

        if (!empty($fromDate)) {
            $query->whereDate('transaction_date', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $query->whereDate('transaction_date', '<=', $toDate);
        }

        $totalCredit = (clone $query)->where('type', 'credit')->sum('amount');
        $totalPayment = (clone $query)->where('type', 'payment')->sum('amount');

        // Payment mode breakdown
        $modeBreakdown = (clone $query)
            ->where('type', 'payment')
            ->selectRaw('payment_mode, SUM(amount) as total_amount, COUNT(*) as count')
            ->groupBy('payment_mode')
            ->get();

        $transactions = $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get();

        $netChange = bcsub((string) $totalCredit, (string) $totalPayment, 2);

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'total_credit' => number_format((float) $totalCredit, 2, '.', ''),
            'total_payment' => number_format((float) $totalPayment, 2, '.', ''),
            'net_change' => $netChange,
            'transaction_count' => $transactions->count(),
            'mode_breakdown' => $modeBreakdown,
            'transactions' => $transactions,
        ];
    }

    /**
     * Get Outstanding Customers Report.
     */
    public function getOutstandingCustomers(int $tenantId): array
    {
        $customers = Customer::where('tenant_id', $tenantId)
            ->where('current_balance', '>', 0)
            ->orderBy('current_balance', 'desc')
            ->get();

        $totalOutstanding = $customers->sum('current_balance');

        return [
            'customers' => $customers,
            'total_outstanding' => number_format((float) $totalOutstanding, 2, '.', ''),
            'count' => $customers->count(),
        ];
    }

    /**
     * Get Customer-wise summary report.
     */
    public function getCustomerWiseSummary(int $tenantId, ?string $search = null): array
    {
        $query = Customer::where('tenant_id', $tenantId);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name', 'asc')->get();

        // Calculate credit and payment totals for each customer
        $customersData = [];
        $totalReceivable = '0.00';
        $totalAdvance = '0.00';

        foreach ($customers as $cust) {
            $creditSum = Transaction::where('tenant_id', $tenantId)
                ->where('customer_id', $cust->id)
                ->where('type', 'credit')
                ->where('is_reversed', false)
                ->sum('amount');

            $paymentSum = Transaction::where('tenant_id', $tenantId)
                ->where('customer_id', $cust->id)
                ->where('type', 'payment')
                ->where('is_reversed', false)
                ->sum('amount');

            if (bccomp((string) $cust->current_balance, '0.00', 2) === 1) {
                $totalReceivable = bcadd($totalReceivable, (string) $cust->current_balance, 2);
            } elseif (bccomp((string) $cust->current_balance, '0.00', 2) === -1) {
                $totalAdvance = bcadd($totalAdvance, abs((float) $cust->current_balance), 2);
            }

            $customersData[] = [
                'id' => $cust->id,
                'name' => $cust->name,
                'phone' => $cust->phone,
                'credit_limit' => $cust->credit_limit,
                'total_credit' => number_format((float) $creditSum, 2, '.', ''),
                'total_payment' => number_format((float) $paymentSum, 2, '.', ''),
                'current_balance' => $cust->current_balance,
                'last_transaction_at' => $cust->last_transaction_at ? $cust->last_transaction_at->format('d M Y') : 'N/A',
            ];
        }

        return [
            'customers' => $customersData,
            'total_receivable' => $totalReceivable,
            'total_advance' => $totalAdvance,
            'count' => count($customersData),
        ];
    }

    /**
     * Get Collection Report.
     */
    public function getCollectionReport(int $tenantId, ?string $fromDate, ?string $toDate): array
    {
        $query = Transaction::with(['customer', 'user'])
            ->where('tenant_id', $tenantId)
            ->where('type', 'payment')
            ->where('is_reversed', false);

        if (!empty($fromDate)) {
            $query->whereDate('transaction_date', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $query->whereDate('transaction_date', '<=', $toDate);
        }

        $totalCollected = (clone $query)->sum('amount');

        $modeBreakdown = (clone $query)
            ->selectRaw('payment_mode, SUM(amount) as total_amount, COUNT(*) as count')
            ->groupBy('payment_mode')
            ->get();

        $payments = $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get();

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'total_collected' => number_format((float) $totalCollected, 2, '.', ''),
            'count' => $payments->count(),
            'mode_breakdown' => $modeBreakdown,
            'payments' => $payments,
        ];
    }
}
