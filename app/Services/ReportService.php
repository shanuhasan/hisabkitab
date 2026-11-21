<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get aggregated financial KPIs for a shop.
     */
    public function getShopOverviewMetrics(int $tenantId): array
    {
        $totalUdhaar = Customer::where('tenant_id', $tenantId)
            ->where('current_balance', '>', 0)
            ->sum('current_balance');

        $totalAdvance = Customer::where('tenant_id', $tenantId)
            ->where('current_balance', '<', 0)
            ->sum('current_balance');

        $totalCustomers = Customer::where('tenant_id', $tenantId)->count();

        $todayCredit = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'credit')
            ->whereDate('transaction_date', Carbon::today())
            ->sum('amount');

        $todayPayment = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'payment')
            ->whereDate('transaction_date', Carbon::today())
            ->sum('amount');

        return [
            'total_udhaar_receivable' => number_format((float) $totalUdhaar, 2, '.', ''),
            'total_advance_payable' => number_format(abs((float) $totalAdvance), 2, '.', ''),
            'total_customers' => $totalCustomers,
            'today_credit' => number_format((float) $todayCredit, 2, '.', ''),
            'today_payment' => number_format((float) $todayPayment, 2, '.', ''),
        ];
    }
}

