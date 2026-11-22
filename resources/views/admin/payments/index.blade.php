@extends('layouts.app')

@section('title', 'Payment Auditing — Super Admin')
@section('header', 'Subscription Payment Management')
@section('subheader', 'Audit payment gateway receipts, verify order references, and manage refunds')

@section('header-actions')
<a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
    &larr; Admin Dashboard
</a>
@endsection

@section('content')
<!-- Filter & Summary Bar -->
<div class="row g-3 mb-4">
    <div class="col-lg-3">
        <div class="card card-hk h-100 border-start border-4 border-success">
            <div class="card-body">
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Total Filtered Revenue</span>
                <h3 class="fw-bold text-success mb-1">₹ {{ number_format((float) $totalCollected, 2) }}</h3>
                <small class="text-muted">Total successful transactions</small>
            </div>
        </div>
    </div>
    <div class="col-lg-9">
        <div class="card card-hk h-100 shadow-sm">
            <div class="card-body p-3">
                <form action="{{ route('admin.payments.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Order ID, Payment ID, or Shop..." value="{{ $search }}">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm">
                            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Status (All)</option>
                            <option value="successful" {{ $status === 'successful' ? 'selected' : '' }}>Successful</option>
                            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="refund_status" class="form-select form-select-sm">
                            <option value="all" {{ $refundStatus === 'all' ? 'selected' : '' }}>Refund (All)</option>
                            <option value="none" {{ $refundStatus === 'none' ? 'selected' : '' }}>No Refund</option>
                            <option value="refunded" {{ $refundStatus === 'refunded' ? 'selected' : '' }}>Refunded</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-1">
                        <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}" title="From Date">
                        <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}" title="To Date">
                    </div>
                    <div class="col-md-1 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100" title="Apply Filter">
                            <i class="bi bi-filter"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Payments Ledger -->
<div class="card card-hk shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-cash-stack me-2 text-primary"></i> Gateway Transactions Journal ({{ $payments->total() }} Total)
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4" style="width: 15%;">Date &amp; Time</th>
                    <th style="width: 20%;">Shop / Merchant</th>
                    <th style="width: 20%;">Gateway Order &amp; Ref</th>
                    <th style="width: 15%;">Plan</th>
                    <th style="width: 12%;">Amount</th>
                    <th style="width: 10%;">Status</th>
                    <th class="text-center pe-4" style="width: 8%;">Refund</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $pmt)
                    <tr>
                        <td class="ps-4 small text-dark fw-semibold">
                            {{ $pmt->created_at ? $pmt->created_at->format('d M Y, h:i A') : 'N/A' }}
                        </td>
                        <td>
                            @if ($pmt->tenant)
                                <a href="{{ route('admin.shops.show', $pmt->tenant) }}" class="fw-bold text-dark text-decoration-none small d-block">
                                    {{ $pmt->tenant->name }}
                                </a>
                                <small class="text-muted" style="font-size: 0.75rem;">+91 {{ $pmt->tenant->phone }}</small>
                            @else
                                <span class="text-muted small">Merchant</span>
                            @endif
                        </td>
                        <td>
                            <code class="small">{{ $pmt->gateway_order_id ?? 'N/A' }}</code>
                            @if ($pmt->gateway_payment_id)
                                <br><small class="text-muted font-monospace" style="font-size: 0.72rem;">{{ $pmt->gateway_payment_id }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ $pmt->plan ? $pmt->plan->name : 'SaaS Plan' }}
                            </span>
                        </td>
                        <td>
                            <strong class="text-dark">₹ {{ number_format((float) $pmt->amount, 2) }}</strong>
                        </td>
                        <td>
                            @if ($pmt->status === 'successful')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Success</span>
                            @elseif ($pmt->status === 'pending')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Pending</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" title="{{ $pmt->error_description }}">Failed</span>
                            @endif
                        </td>
                        <td class="text-center pe-4">
                            @if ($pmt->refund_status === 'refunded')
                                <span class="badge bg-secondary px-2 py-1" title="Refunded on {{ $pmt->refunded_at ? $pmt->refunded_at->format('d M Y') : '' }}">
                                    Refunded
                                </span>
                            @elseif ($pmt->status === 'successful')
                                <form action="{{ route('admin.payments.refund', $pmt) }}" method="POST" onsubmit="return confirm('Confirm refund for ₹{{ $pmt->amount }}?');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" style="font-size: 0.75rem;" title="Process Refund">
                                        Refund
                                    </button>
                                </form>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted small">
                            No payment transactions match the specified filter criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($payments->hasPages())
        <div class="card-footer bg-white border-top py-3">
            {{ $payments->links() }}
        </div>
    @endif
</div>
@endsection

