@extends('layouts.app')

@section('title', 'Financial Reports — HisabKitab')
@section('header', 'Business & Financial Reports')
@section('subheader', 'Audited financial summaries, customer balances, and collections')

@section('header-actions')
<div class="d-flex flex-wrap gap-2">
    <!-- Export PDF -->
    <a href="{{ route('reports.export.pdf', ['report' => $reportType, 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="btn btn-outline-danger btn-sm fw-semibold shadow-sm">
        <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
    </a>

    <!-- Export CSV -->
    <a href="{{ route('reports.export.csv', ['report' => $reportType, 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="btn btn-outline-success btn-sm fw-semibold shadow-sm">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV / Excel
    </a>

    <!-- Print Button -->
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm fw-semibold">
        <i class="bi bi-printer me-1"></i> Print
    </button>
</div>
@endsection

@section('content')
<!-- Report Tabs -->
<ul class="nav nav-pills mb-4 gap-2 bg-white p-2 rounded-3 border shadow-sm">
    <li class="nav-item">
        <a class="nav-link fw-semibold {{ $reportType === 'period' ? 'active' : '' }}" href="{{ route('reports.index', ['report' => 'period', 'period' => $period]) }}">
            <i class="bi bi-calendar-range me-1"></i> Period Statement
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-semibold {{ $reportType === 'customer' ? 'active' : '' }}" href="{{ route('reports.index', ['report' => 'customer']) }}">
            <i class="bi bi-people me-1"></i> Customer-wise Summary
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-semibold {{ $reportType === 'outstanding' ? 'active' : '' }}" href="{{ route('reports.index', ['report' => 'outstanding']) }}">
            <i class="bi bi-exclamation-triangle me-1"></i> Outstanding Customers
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-semibold {{ $reportType === 'collection' ? 'active' : '' }}" href="{{ route('reports.index', ['report' => 'collection', 'period' => $period]) }}">
            <i class="bi bi-cash-stack me-1"></i> Payment Collections
        </a>
    </li>
</ul>

@if ($reportType === 'period' || $reportType === 'collection')
    <!-- Period Selector Controls -->
    <div class="card card-hk mb-4 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('reports.index') }}" class="row g-2 align-items-center">
                <input type="hidden" name="report" value="{{ $reportType }}">

                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1 fw-semibold">Quick Period</label>
                    <div class="btn-group w-100" role="group">
                        <a href="{{ route('reports.index', ['report' => $reportType, 'period' => 'daily']) }}" class="btn btn-sm {{ $period === 'daily' ? 'btn-primary' : 'btn-light border' }}">Daily</a>
                        <a href="{{ route('reports.index', ['report' => $reportType, 'period' => 'weekly']) }}" class="btn btn-sm {{ $period === 'weekly' ? 'btn-primary' : 'btn-light border' }}">Weekly</a>
                        <a href="{{ route('reports.index', ['report' => $reportType, 'period' => 'monthly']) }}" class="btn btn-sm {{ $period === 'monthly' ? 'btn-primary' : 'btn-light border' }}">Monthly</a>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1 fw-semibold">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1 fw-semibold">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                </div>

                <div class="col-md-3 text-end pt-3">
                    <input type="hidden" name="period" value="custom">
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold w-100">
                        <i class="bi bi-filter me-1"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

<!-- REPORT CONTENT SECTION -->

@if ($reportType === 'period')
    <!-- Period Summary KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="card card-hk border-start border-danger border-4">
                <div class="card-body py-3">
                    <div class="text-muted small fw-semibold">Total Udhaar Given</div>
                    <div class="h4 fw-bold text-danger mb-0">₹ {{ $periodData['total_credit'] }}</div>
                    <small class="text-muted">In selected period</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card card-hk border-start border-success border-4">
                <div class="card-body py-3">
                    <div class="text-muted small fw-semibold">Total Payments Received</div>
                    <div class="h4 fw-bold text-success mb-0">₹ {{ $periodData['total_payment'] }}</div>
                    <small class="text-muted">In selected period</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card card-hk border-start border-primary border-4">
                <div class="card-body py-3">
                    <div class="text-muted small fw-semibold">Net Ledger Change</div>
                    <div class="h4 fw-bold {{ bccomp($periodData['net_change'], '0.00', 2) === 1 ? 'text-danger' : 'text-success' }} mb-0">
                        {{ bccomp($periodData['net_change'], '0.00', 2) === 1 ? '+' : '' }} ₹ {{ number_format((float) $periodData['net_change'], 2) }}
                    </div>
                    <small class="text-muted">Credit minus Payment</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card card-hk border-start border-info border-4">
                <div class="card-body py-3">
                    <div class="text-muted small fw-semibold">Total Transactions</div>
                    <div class="h4 fw-bold text-slate-800 mb-0">{{ $periodData['transaction_count'] }}</div>
                    <small class="text-muted">Audit entries</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Mode Breakdown -->
    @if ($periodData['mode_breakdown']->isNotEmpty())
        <div class="card card-hk mb-4 shadow-sm">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-wallet2 text-primary me-2"></i> Payment Mode Breakdown (Collections)
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    @foreach ($periodData['mode_breakdown'] as $mb)
                        <div class="col-sm-6 col-md-3">
                            <div class="bg-light p-3 rounded border text-center">
                                <span class="badge bg-secondary mb-1">{{ ucfirst($mb->payment_mode) }}</span>
                                <div class="fw-bold text-success fs-5">₹ {{ number_format((float) $mb->total_amount, 2) }}</div>
                                <small class="text-muted">{{ $mb->count }} payments</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Transactions Register Table -->
    <div class="card card-hk shadow-sm">
        <div class="card-header bg-white py-3 fw-bold">
            <i class="bi bi-list-check text-primary me-2"></i> Transactions in Period ({{ $periodData['transaction_count'] }})
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Date & Ref</th>
                        <th>Customer</th>
                        <th>Type & Mode</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end pe-4">Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($periodData['transactions'] as $txn)
                        <tr>
                            <td class="ps-4 py-2">
                                <div class="fw-semibold text-dark small">{{ $txn->transaction_date->format('d M Y') }}</div>
                                <small class="font-monospace text-muted">{{ $txn->transaction_number }}</small>
                            </td>
                            <td>
                                <a href="{{ route('customers.show', $txn->customer) }}" class="fw-semibold text-dark text-decoration-none small">
                                    {{ $txn->customer->name ?? 'N/A' }}
                                </a>
                                <div class="text-muted small" style="font-size: 0.75rem;">+91 {{ $txn->customer->phone ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $txn->type === 'credit' ? 'badge-credit' : 'badge-payment' }}">
                                    {{ $txn->type === 'credit' ? 'Udhaar Dena' : 'Jama Lena' }}
                                </span>
                                <span class="badge bg-light text-dark border">{{ ucfirst($txn->payment_mode) }}</span>
                            </td>
                            <td class="text-end fw-bold {{ $txn->type === 'credit' ? 'text-danger' : 'text-success' }}">
                                {{ $txn->type === 'credit' ? '+' : '-' }} ₹ {{ number_format((float) $txn->amount, 2) }}
                            </td>
                            <td class="text-end pe-4 fw-semibold text-slate-800">
                                ₹ {{ number_format((float) $txn->running_balance, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted small">No transactions found for the selected period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@elseif ($reportType === 'customer')
    <!-- Customer-wise Summary Report -->
    <div class="card card-hk mb-4 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="bi bi-people me-2 text-primary"></i> Customer Ledger Summary ({{ $customerData['count'] }} Customers)</span>
            <div class="d-flex gap-3 small">
                <span class="text-danger fw-bold">Total Udhaar: ₹ {{ number_format((float) $customerData['total_receivable'], 2) }}</span>
                <span class="text-success fw-bold">Total Advance: ₹ {{ number_format((float) $customerData['total_advance'], 2) }}</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Customer Name & Phone</th>
                        <th class="text-end">Total Udhaar</th>
                        <th class="text-end">Total Jama</th>
                        <th class="text-end">Current Balance</th>
                        <th class="text-end pe-4">Last Active</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customerData['customers'] as $cust)
                        <tr>
                            <td class="ps-4 py-2">
                                <a href="{{ route('customers.show', $cust['id']) }}" class="fw-bold text-dark text-decoration-none">
                                    {{ $cust['name'] }}
                                </a>
                                <div class="text-muted small">+91 {{ $cust['phone'] }}</div>
                            </td>
                            <td class="text-end text-danger fw-semibold">₹ {{ number_format((float) $cust['total_credit'], 2) }}</td>
                            <td class="text-end text-success fw-semibold">₹ {{ number_format((float) $cust['total_payment'], 2) }}</td>
                            <td class="text-end">
                                <div class="fw-bold {{ $cust['current_balance'] > 0 ? 'text-danger' : ($cust['current_balance'] < 0 ? 'text-success' : 'text-muted') }}">
                                    ₹ {{ number_format((float) $cust['current_balance'], 2) }}
                                </div>
                            </td>
                            <td class="text-end pe-4 text-muted small">{{ $cust['last_transaction_at'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted small">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@elseif ($reportType === 'outstanding')
    <!-- Outstanding Customers Report -->
    <div class="card card-hk mb-4 shadow-sm border-top border-danger border-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> Outstanding Customers Report ({{ $outstandingData['count'] }})
            </span>
            <span class="h5 fw-bold text-danger mb-0">
                Total Due: ₹ {{ number_format((float) $outstandingData['total_outstanding'], 2) }}
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Customer Details</th>
                        <th>Contact / Phone</th>
                        <th>Store Address</th>
                        <th class="text-end pe-4">Pending Udhaar Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($outstandingData['customers'] as $cust)
                        <tr>
                            <td class="ps-4 py-3">
                                <a href="{{ route('customers.show', $cust) }}" class="fw-bold text-dark text-decoration-none">
                                    {{ $cust->name }}
                                </a>
                            </td>
                            <td>+91 {{ $cust->phone }}</td>
                            <td class="text-muted small">{{ $cust->address ?: 'N/A' }}</td>
                            <td class="text-end pe-4">
                                <div class="h5 fw-bold text-danger mb-0">₹ {{ number_format((float) $cust->current_balance, 2) }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted small">
                                <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-1"></i>
                                Excellent! No customers currently have pending udhaar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@elseif ($reportType === 'collection')
    <!-- Collection Report -->
    <div class="card card-hk mb-4 shadow-sm border-top border-success border-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-success">
                <i class="bi bi-cash-stack me-2"></i> Payment Collections Report ({{ $collectionData['count'] }} Entries)
            </span>
            <span class="h5 fw-bold text-success mb-0">
                Total Collected: ₹ {{ number_format((float) $collectionData['total_collected'], 2) }}
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Date & Ref</th>
                        <th>Customer</th>
                        <th>Payment Mode</th>
                        <th class="text-end pe-4">Amount Received</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($collectionData['payments'] as $p)
                        <tr>
                            <td class="ps-4 py-2">
                                <div class="fw-semibold text-dark small">{{ $p->transaction_date->format('d M Y') }}</div>
                                <small class="font-monospace text-muted">{{ $p->transaction_number }}</small>
                            </td>
                            <td>
                                <a href="{{ route('customers.show', $p->customer) }}" class="fw-semibold text-dark text-decoration-none small">
                                    {{ $p->customer->name ?? 'N/A' }}
                                </a>
                                <div class="text-muted small" style="font-size: 0.75rem;">+91 {{ $p->customer->phone ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ ucfirst($p->payment_mode) }}</span>
                                @if ($p->reference_number)
                                    <small class="text-muted font-monospace d-block">{{ $p->reference_number }}</small>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="text-success fw-bold fs-5">+ ₹ {{ number_format((float) $p->amount, 2) }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted small">No payments collected in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection

