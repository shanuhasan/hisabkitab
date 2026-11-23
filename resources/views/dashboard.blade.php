@extends('layouts.app')

@section('title', 'Dashboard — ' . ($tenant->name ?? 'UdhaarHisab'))
@section('header', ($tenant->name ?? 'Shop') . ' Dashboard')
@section('subheader', 'Real-time overview of customer credit, collections, and financial analytics')

@section('header-actions')
<div class="d-flex flex-wrap gap-2">
    <a href="{{ route('transactions.credit') }}" class="btn btn-danger btn-sm fw-semibold shadow-sm">
        <i class="bi bi-arrow-up-right-circle me-1"></i> + Udhaar Dena
    </a>
    <a href="{{ route('transactions.payment') }}" class="btn btn-success btn-sm fw-semibold shadow-sm">
        <i class="bi bi-arrow-down-left-circle me-1"></i> + Jama Lena
    </a>
    <a href="{{ route('reports.index') }}" class="btn btn-outline-primary btn-sm fw-semibold">
        <i class="bi bi-bar-chart-line me-1"></i> View Reports
    </a>
</div>
@endsection

@section('content')
<!-- Top 6 Statistics Cards -->
<div class="row g-3 mb-4">
    <!-- 1. Total Outstanding Balance -->
    <div class="col-sm-6 col-xl-4 col-xxl-2">
        <div class="card card-hk h-100 border-start border-danger border-4">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">Total Outstanding</span>
                    <i class="bi bi-exclamation-circle text-danger"></i>
                </div>
                <div class="h4 fw-bold text-danger mb-0">₹ {{ $dashboardData['metrics']['total_outstanding_balance'] }}</div>
                <small class="text-muted" style="font-size: 0.75rem;">To collect from customers</small>
            </div>
        </div>
    </div>

    <!-- 2. Today's Udhaar -->
    <div class="col-sm-6 col-xl-4 col-xxl-2">
        <div class="card card-hk h-100 border-start border-danger border-4">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">Today's Udhaar</span>
                    <i class="bi bi-arrow-up-right-circle text-danger"></i>
                </div>
                <div class="h4 fw-bold text-slate-800 mb-0">₹ {{ $dashboardData['metrics']['today_udhaar'] }}</div>
                <small class="text-muted" style="font-size: 0.75rem;">Credit given today</small>
            </div>
        </div>
    </div>

    <!-- 3. Today's Collection -->
    <div class="col-sm-6 col-xl-4 col-xxl-2">
        <div class="card card-hk h-100 border-start border-success border-4">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">Today's Collection</span>
                    <i class="bi bi-arrow-down-left-circle text-success"></i>
                </div>
                <div class="h4 fw-bold text-success mb-0">₹ {{ $dashboardData['metrics']['today_collection'] }}</div>
                <small class="text-muted" style="font-size: 0.75rem;">Payments received today</small>
            </div>
        </div>
    </div>

    <!-- 4. Total Udhaar Given (All-Time) -->
    <div class="col-sm-6 col-xl-4 col-xxl-2">
        <div class="card card-hk h-100 border-start border-primary border-4">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">Total Credit (All-Time)</span>
                    <i class="bi bi-journal-text text-primary"></i>
                </div>
                <div class="h4 fw-bold text-slate-800 mb-0">₹ {{ $dashboardData['metrics']['total_udhaar_given'] }}</div>
                <small class="text-muted" style="font-size: 0.75rem;">All non-reversed credit</small>
            </div>
        </div>
    </div>

    <!-- 5. Total Payments Received (All-Time) -->
    <div class="col-sm-6 col-xl-4 col-xxl-2">
        <div class="card card-hk h-100 border-start border-success border-4">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">Total Payments (All-Time)</span>
                    <i class="bi bi-cash-coin text-success"></i>
                </div>
                <div class="h4 fw-bold text-slate-800 mb-0">₹ {{ $dashboardData['metrics']['total_payments_received'] }}</div>
                <small class="text-muted" style="font-size: 0.75rem;">All payments received</small>
            </div>
        </div>
    </div>

    <!-- 6. Total Customers -->
    <div class="col-sm-6 col-xl-4 col-xxl-2">
        <div class="card card-hk h-100 border-start border-info border-4">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">Active Customers</span>
                    <i class="bi bi-people-fill text-info"></i>
                </div>
                <div class="h4 fw-bold text-slate-800 mb-0">{{ $dashboardData['metrics']['total_customers'] }}</div>
                <small class="text-muted" style="font-size: 0.75rem;">Registered grahak</small>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row: Daily Collection & Monthly Comparison -->
<div class="row g-3 mb-4">
    <!-- Daily Collection Chart (Last 14 Days) -->
    <div class="col-lg-6">
        <div class="card card-hk h-100 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark">
                    <i class="bi bi-graph-up-arrow text-success me-2"></i> Daily Payments Received (Last 14 Days)
                </span>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Collection Trend</span>
            </div>
            <div class="card-body p-3">
                <div style="height: 240px; position: relative;">
                    <canvas id="dailyCollectionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Udhaar vs Payment Comparison (Last 6 Months) -->
    <div class="col-lg-6">
        <div class="card card-hk h-100 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark">
                    <i class="bi bi-bar-chart-fill text-primary me-2"></i> Monthly Credit vs Payment (Last 6 Months)
                </span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Len-Den Ratio</span>
            </div>
            <div class="card-body p-3">
                <div style="height: 240px; position: relative;">
                    <canvas id="monthlyComparisonChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Middle Section: Recent Transactions & Highest Outstanding Customers -->
<div class="row g-3 mb-4">
    <!-- Recent Transactions -->
    <div class="col-lg-7">
        <div class="card card-hk h-100 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark">
                    <i class="bi bi-clock-history text-primary me-2"></i> Recent Transactions
                </span>
                <a href="{{ route('transactions.history') }}" class="btn btn-link btn-sm text-decoration-none p-0">
                    View All &rarr;
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 25%;">Date & Ref</th>
                            <th style="width: 35%;">Customer</th>
                            <th class="text-end pe-3" style="width: 40%;">Amount & Running Bal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dashboardData['recent_transactions'] as $txn)
                            <tr>
                                <td class="ps-3 py-2">
                                    <div class="small fw-semibold text-dark">{{ $txn->transaction_date->format('d M') }}</div>
                                    <small class="font-monospace text-muted" style="font-size: 0.75rem;">{{ $txn->transaction_number }}</small>
                                </td>
                                <td>
                                    @if ($txn->customer)
                                        <a href="{{ route('customers.show', $txn->customer) }}" class="fw-semibold text-dark text-decoration-none small d-block text-truncate" style="max-width: 180px;">
                                            {{ $txn->customer->name }}
                                        </a>
                                    @else
                                        <span class="text-muted small">Customer</span>
                                    @endif
                                    <span class="badge {{ $txn->type === 'credit' ? 'badge-credit' : ($txn->type === 'payment' ? 'badge-payment' : 'bg-secondary') }}" style="font-size: 0.7rem;">
                                        {{ $txn->type === 'credit' ? '+ Udhaar' : ($txn->type === 'payment' ? '- Jama' : 'Reversal') }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="{{ $txn->type === 'credit' ? 'amount-credit' : ($txn->type === 'payment' ? 'amount-payment' : 'text-secondary') }} fw-bold small">
                                        {{ $txn->type === 'credit' ? '+' : ($txn->type === 'payment' ? '-' : '') }} ₹ {{ number_format((float) $txn->amount, 2) }}
                                    </div>
                                    <small class="text-muted" style="font-size: 0.72rem;">Bal: ₹ {{ number_format((float) $txn->running_balance, 2) }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted small">
                                    No transactions recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Highest Outstanding Customers (Top Due Accounts) -->
    <div class="col-lg-5">
        <div class="card card-hk h-100 shadow-sm border-top border-danger border-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark">
                    <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Highest Outstanding
                </span>
                <a href="{{ route('customers.index', ['balance' => 'receivable']) }}" class="btn btn-link btn-sm text-decoration-none p-0">
                    All Due &rarr;
                </a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse ($dashboardData['highest_outstanding_customers'] as $cust)
                        <div class="list-group-item d-flex align-items-center justify-content-between px-3 py-2">
                            <div>
                                <a href="{{ route('customers.show', $cust) }}" class="fw-bold text-dark text-decoration-none small d-block">
                                    {{ $cust->name }}
                                </a>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    <i class="bi bi-telephone me-1"></i>+91 {{ $cust->phone }}
                                </small>
                            </div>
                            <div class="text-end">
                                <div class="text-danger fw-bold">₹ {{ number_format((float) $cust->current_balance, 2) }}</div>
                                <a href="https://wa.me/91{{ preg_replace('/[^0-9]/', '', $cust->phone) }}?text={{ rawurlencode('Namaste ' . $cust->name . ' ji, aapka baki hisab ₹ ' . number_format((float) $cust->current_balance, 2) . ' hai. Kripya bhugtan karein.') }}" target="_blank" class="btn btn-outline-success btn-sm py-0 px-2 mt-1" style="font-size: 0.75rem;" title="Send WhatsApp Reminder">
                                    <i class="bi bi-whatsapp"></i> Remind
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-check-circle-fill text-success fs-4 d-block mb-1"></i>
                            No pending udhaar balance! All customer accounts are settled.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Section: Recent Payments List -->
<div class="card card-hk shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-bold text-dark">
            <i class="bi bi-cash-stack text-success me-2"></i> Recent Payments Received (Jama)
        </span>
        <a href="{{ route('transactions.history', ['type' => 'payment']) }}" class="btn btn-link btn-sm text-decoration-none p-0">
            View All Payments &rarr;
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4" style="width: 20%;">Date & Ref</th>
                    <th style="width: 30%;">Customer Name</th>
                    <th style="width: 20%;">Payment Method</th>
                    <th class="text-end pe-4" style="width: 30%;">Amount Received</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dashboardData['recent_payments'] as $pay)
                    <tr>
                        <td class="ps-4 py-2">
                            <div class="small fw-semibold text-dark">{{ $pay->transaction_date->format('d M Y') }}</div>
                            <small class="font-monospace text-muted" style="font-size: 0.75rem;">{{ $pay->transaction_number }}</small>
                        </td>
                        <td>
                            <a href="{{ route('customers.show', $pay->customer) }}" class="fw-semibold text-dark text-decoration-none small">
                                {{ $pay->customer->name ?? 'N/A' }}
                            </a>
                            <div class="text-muted small" style="font-size: 0.75rem;">+91 {{ $pay->customer->phone ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ ucfirst($pay->payment_mode) }}</span>
                            @if ($pay->reference_number)
                                <div class="text-muted font-monospace" style="font-size: 0.72rem;">{{ $pay->reference_number }}</div>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <div class="amount-payment fs-5">+ ₹ {{ number_format((float) $pay->amount, 2) }}</div>
                            <small class="text-muted" style="font-size: 0.75rem;">Bal: ₹ {{ number_format((float) $pay->running_balance, 2) }}</small>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-3 text-muted small">
                            No payment transactions recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Daily Collection Chart
    const dailyCtx = document.getElementById('dailyCollectionChart');
    if (dailyCtx) {
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: @json($dashboardData['daily_chart']['labels']),
                datasets: [{
                    label: 'Daily Collection (₹)',
                    data: @json($dashboardData['daily_chart']['data']),
                    borderColor: '#15803d',
                    backgroundColor: 'rgba(21, 128, 61, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#15803d',
                    pointRadius: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' ₹ ' + context.parsed.y.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₹ ' + value; }
                        }
                    }
                }
            }
        });
    }

    // 2. Monthly Comparison Chart (Credit vs Payment)
    const monthlyCtx = document.getElementById('monthlyComparisonChart');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: @json($dashboardData['monthly_chart']['labels']),
                datasets: [
                    {
                        label: 'Udhaar Given (₹)',
                        data: @json($dashboardData['monthly_chart']['credit']),
                        backgroundColor: '#dc2626',
                        borderRadius: 4,
                    },
                    {
                        label: 'Jama Received (₹)',
                        data: @json($dashboardData['monthly_chart']['payment']),
                        backgroundColor: '#16a34a',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 12 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ₹ ' + context.parsed.y.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₹ ' + value; }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
