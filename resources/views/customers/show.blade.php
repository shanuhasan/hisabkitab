@extends('layouts.app')

@section('title', $customer->name . ' — Customer Ledger')
@section('header', $customer->name)
@section('subheader', '+91 ' . $customer->phone . ($customer->address ? ' • ' . $customer->address : ''))

@section('header-actions')
<div class="d-flex flex-wrap gap-2">
    <!-- Transaction Buttons -->
    <a href="{{ route('transactions.credit', ['customer_id' => $customer->id]) }}" class="btn btn-danger fw-semibold shadow-sm">
        <i class="bi bi-arrow-up-right-circle me-1"></i> + Udhaar Dena
    </a>
    <a href="{{ route('transactions.payment', ['customer_id' => $customer->id]) }}" class="btn btn-success fw-semibold shadow-sm">
        <i class="bi bi-arrow-down-left-circle me-1"></i> + Jama Lena
    </a>

    <!-- Print Statement -->
    <a href="{{ route('customers.ledger', $customer) }}" target="_blank" class="btn btn-outline-dark fw-semibold">
        <i class="bi bi-printer me-1"></i> Statement
    </a>

    <!-- WhatsApp Reminder -->
    <a href="{{ $whatsappUrl }}" target="_blank" class="btn btn-outline-success fw-semibold" title="Send WhatsApp Payment Reminder">
        <i class="bi bi-whatsapp me-1"></i> WhatsApp
    </a>

    <!-- Direct Phone Call -->
    <a href="tel:+91{{ $customer->phone }}" class="btn btn-outline-secondary fw-semibold">
        <i class="bi bi-telephone-fill me-1"></i> Call
    </a>

    <!-- Edit Customer -->
    @if (!$customer->trashed())
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-primary fw-semibold">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>

        <!-- Archive Customer -->
        <form method="POST" action="{{ route('customers.archive', $customer) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to archive {{ $customer->name }}?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger fw-semibold">
                <i class="bi bi-archive me-1"></i> Archive
            </button>
        </form>
    @else
        <!-- Restore Customer -->
        <form method="POST" action="{{ route('customers.restore', $customer->id) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success fw-semibold">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Restore Customer
            </button>
        </form>
    @endif
</div>
@endsection

@section('content')
<!-- Customer Financial Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Current Outstanding Balance -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-hk h-100 border-start border-4 {{ $customer->current_balance > 0 ? 'border-danger' : ($customer->current_balance < 0 ? 'border-success' : 'border-secondary') }}">
            <div class="card-body">
                <div class="text-muted small fw-semibold mb-1">Current Balance</div>
                @if ($customer->current_balance > 0)
                    <h3 class="fw-bold text-danger mb-1">₹ {{ number_format((float) $customer->current_balance, 2) }}</h3>
                    <span class="badge badge-credit">You'll Get (Udhaar)</span>
                @elseif ($customer->current_balance < 0)
                    <h3 class="fw-bold text-success mb-1">₹ {{ number_format(abs((float) $customer->current_balance), 2) }}</h3>
                    <span class="badge badge-payment">You'll Give (Advance)</span>
                @else
                    <h3 class="fw-bold text-slate-800 mb-1">₹ 0.00</h3>
                    <span class="badge bg-light text-secondary border">Fully Settled</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Total Udhaar Given -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-hk h-100 border-start border-danger border-4">
            <div class="card-body">
                <div class="text-muted small fw-semibold mb-1">Total Udhaar (All-Time)</div>
                <h3 class="fw-bold text-slate-800 mb-1">₹ {{ $customer->total_credit }}</h3>
                <small class="text-muted">Total credit given</small>
            </div>
        </div>
    </div>

    <!-- Total Payments Received -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-hk h-100 border-start border-success border-4">
            <div class="card-body">
                <div class="text-muted small fw-semibold mb-1">Total Jama (All-Time)</div>
                <h3 class="fw-bold text-slate-800 mb-1">₹ {{ $customer->total_payment }}</h3>
                <small class="text-muted">Total payments received</small>
            </div>
        </div>
    </div>

    <!-- Credit Limit & Info -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-hk h-100 border-start border-primary border-4">
            <div class="card-body">
                <div class="text-muted small fw-semibold mb-1">Credit Limit</div>
                <h3 class="fw-bold text-slate-800 mb-1">
                    {{ $customer->credit_limit > 0 ? '₹ ' . number_format((float) $customer->credit_limit, 2) : 'No Limit' }}
                </h3>
                <small class="text-muted">
                    Status: <span class="badge {{ $customer->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary' }}">{{ ucfirst($customer->status) }}</span>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Customer Notes & Details Banner -->
@if ($customer->notes)
    <div class="alert alert-light border shadow-sm mb-4">
        <div class="d-flex align-items-start">
            <i class="bi bi-sticky-fill text-warning fs-5 me-2 mt-1"></i>
            <div>
                <strong class="d-block text-dark small">Customer Notes:</strong>
                <span class="text-muted small">{{ $customer->notes }}</span>
            </div>
        </div>
    </div>
@endif

<!-- Customer Ledger History -->
<div class="card card-hk shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <span class="fw-bold text-dark"><i class="bi bi-receipt me-2 text-primary"></i> Ledger Statement (Len-Den Khata)</span>
        </div>
        <div>
            <span class="badge bg-light text-muted border">
                Total Transactions: {{ $transactions->total() }}
            </span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4" style="width: 20%;">Date & Ref</th>
                    <th style="width: 35%;">Details / Description</th>
                    <th class="text-end" style="width: 22%;">Amount</th>
                    <th class="text-end pe-4" style="width: 23%;">Running Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $txn)
                    <tr>
                        <td class="ps-4 py-3">
                            <div class="fw-semibold text-dark">{{ $txn->transaction_date->format('d M Y') }}</div>
                            <small class="font-monospace text-muted">{{ $txn->transaction_number }}</small>
                        </td>
                        <td>
                            <div>{{ $txn->description ?? 'Transaction' }}</div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="badge {{ $txn->type === 'credit' ? 'badge-credit' : ($txn->type === 'payment' ? 'badge-payment' : 'bg-secondary') }}">
                                    {{ $txn->type === 'credit' ? 'Udhaar Dena' : ($txn->type === 'payment' ? 'Jama Lena' : 'Reversal') }}
                                </span>
                                <span class="text-muted small">Mode: {{ ucfirst($txn->payment_mode) }}</span>
                            </div>
                        </td>
                        <td class="text-end">
                            @if ($txn->type === 'credit')
                                <div class="amount-credit fs-5">+ ₹ {{ number_format((float) $txn->amount, 2) }}</div>
                            @elseif ($txn->type === 'payment')
                                <div class="amount-payment fs-5">- ₹ {{ number_format((float) $txn->amount, 2) }}</div>
                            @else
                                <div class="text-secondary fs-5">₹ {{ number_format((float) $txn->amount, 2) }}</div>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <div class="fw-bold {{ $txn->running_balance > 0 ? 'text-danger' : ($txn->running_balance < 0 ? 'text-success' : 'text-muted') }}">
                                ₹ {{ number_format((float) $txn->running_balance, 2) }}
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-receipt-cutoff display-4 text-secondary opacity-50 mb-3 d-inline-block"></i>
                                <h6 class="fw-bold text-dark">No Ledger Entries Yet</h6>
                                <p class="small mb-0">No transactions have been recorded for this customer yet.</p>
                                <p class="small text-muted">In Phase 4, you will record daily Udhaar Dena and Jama Lena entries with bill photo attachments.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($transactions->hasPages())
        <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
            <span class="small text-muted">
                Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} transactions
            </span>
            <div>
                {{ $transactions->links() }}
            </div>
        </div>
    @endif
</div>
@endsection

