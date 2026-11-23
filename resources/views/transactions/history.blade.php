@extends('layouts.app')

@section('title', 'Transaction History — UdhaarHisab')
@section('header', 'Transaction History (Len-Den Register)')
@section('subheader', 'Complete chronological ledger of all customer credits and payments')

@section('header-actions')
<div class="d-flex gap-2">
    <a href="{{ route('transactions.credit') }}" class="btn btn-danger btn-sm fw-semibold shadow-sm">
        <i class="bi bi-arrow-up-right-circle me-1"></i> + Udhaar Dena
    </a>
    <a href="{{ route('transactions.payment') }}" class="btn btn-success btn-sm fw-semibold shadow-sm">
        <i class="bi bi-arrow-down-left-circle me-1"></i> + Jama Lena
    </a>
</div>
@endsection

@section('content')
<!-- Filter Summary Metrics -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-4">
        <div class="card card-hk border-start border-danger border-4">
            <div class="card-body py-3">
                <div class="text-muted small fw-semibold">Filtered Udhaar (Credit Given)</div>
                <div class="h4 fw-bold text-danger mb-0">₹ {{ number_format((float) $totalCredit, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card card-hk border-start border-success border-4">
            <div class="card-body py-3">
                <div class="text-muted small fw-semibold">Filtered Jama (Payments Got)</div>
                <div class="h4 fw-bold text-success mb-0">₹ {{ number_format((float) $totalPayment, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card card-hk border-start border-primary border-4">
            <div class="card-body py-3">
                <div class="text-muted small fw-semibold">Total Filtered Entries</div>
                <div class="h4 fw-bold text-slate-800 mb-0">{{ $transactions->total() }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="card card-hk mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('transactions.history') }}" class="row g-2 align-items-center">
            <!-- Search -->
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search customer, mobile, ref..." value="{{ $search }}">
                </div>
            </div>

            <!-- Transaction Type -->
            <div class="col-md-2">
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All Types</option>
                    <option value="credit" {{ $type === 'credit' ? 'selected' : '' }}>Udhaar Only (+)</option>
                    <option value="payment" {{ $type === 'payment' ? 'selected' : '' }}>Jama Only (-)</option>
                    <option value="reversal" {{ $type === 'reversal' ? 'selected' : '' }}>Reversals Only</option>
                </select>
            </div>

            <!-- Payment Mode -->
            <div class="col-md-2">
                <select name="payment_mode" class="form-select" onchange="this.form.submit()">
                    <option value="all" {{ $paymentMode === 'all' ? 'selected' : '' }}>All Modes</option>
                    <option value="cash" {{ $paymentMode === 'cash' ? 'selected' : '' }}>Cash (Nagad)</option>
                    <option value="upi" {{ $paymentMode === 'upi' ? 'selected' : '' }}>UPI / QR</option>
                    <option value="bank_transfer" {{ $paymentMode === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="cheque" {{ $paymentMode === 'cheque' ? 'selected' : '' }}>Cheque</option>
                    <option value="other" {{ $paymentMode === 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <!-- From Date -->
            <div class="col-md-2">
                <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}" placeholder="From date" onchange="this.form.submit()">
            </div>

            <!-- To Date -->
            <div class="col-md-2">
                <input type="date" name="to_date" class="form-control" value="{{ $toDate }}" placeholder="To date" onchange="this.form.submit()">
            </div>

            <!-- Reset -->
            <div class="col-md-1 text-end">
                <a href="{{ route('transactions.history') }}" class="btn btn-outline-secondary w-100" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Transactions Table -->
<div class="card card-hk shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4" style="width: 18%;">Date & Ref</th>
                    <th style="width: 25%;">Customer</th>
                    <th style="width: 22%;">Description / Mode</th>
                    <th class="text-end" style="width: 18%;">Amount</th>
                    <th class="text-end pe-4" style="width: 17%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $txn)
                    <tr class="{{ $txn->is_reversed ? 'opacity-75 bg-light' : '' }}">
                        <td class="ps-4 py-3">
                            <div class="fw-semibold text-dark">{{ $txn->transaction_date->format('d M Y') }}</div>
                            <small class="font-monospace text-muted">{{ $txn->transaction_number }}</small>
                            @if ($txn->reference_number)
                                <div class="text-muted" style="font-size: 0.75rem;">Ref: {{ $txn->reference_number }}</div>
                            @endif
                        </td>
                        <td>
                            @if ($txn->customer)
                                <a href="{{ route('customers.show', $txn->customer) }}" class="fw-bold text-dark text-decoration-none">
                                    {{ $txn->customer->name }}
                                </a>
                                <div class="text-muted small">
                                    <i class="bi bi-telephone me-1"></i>+91 {{ $txn->customer->phone }}
                                </div>
                            @else
                                <span class="text-muted fst-italic">Customer Deleted</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge {{ $txn->type === 'credit' ? 'badge-credit' : ($txn->type === 'payment' ? 'badge-payment' : 'bg-secondary') }}">
                                    {{ $txn->type === 'credit' ? 'Udhaar Dena' : ($txn->type === 'payment' ? 'Jama Lena' : 'Reversal') }}
                                </span>
                                <span class="badge bg-light text-dark border">{{ ucfirst($txn->payment_mode) }}</span>
                                @if ($txn->is_reversed)
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Reversed</span>
                                @endif
                            </div>
                            <div class="text-muted small text-truncate" style="max-width: 240px;">
                                {{ $txn->description ?? 'No remarks' }}
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
                            <small class="text-muted" style="font-size: 0.75rem;">Bal: ₹ {{ number_format((float) $txn->running_balance, 2) }}</small>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('transactions.show', $txn) }}" class="btn btn-outline-primary" title="View Transaction Details">
                                    <i class="bi bi-eye"></i> Details
                                </a>

                                @if (!$txn->is_reversed && $txn->type !== 'reversal' && auth()->user()->isShopOwner())
                                    <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#reverseModal{{ $txn->id }}" title="Reverse Entry">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                @endif
                            </div>

                            <!-- Reversal Modal -->
                            @if (!$txn->is_reversed && $txn->type !== 'reversal' && auth()->user()->isShopOwner())
                                <div class="modal fade" id="reverseModal{{ $txn->id }}" tabindex="-1" aria-labelledby="reverseModalLabel{{ $txn->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered text-start">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('transactions.reverse', $txn) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold" id="reverseModalLabel{{ $txn->id }}">
                                                        <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Reverse Transaction
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="small text-muted mb-3">
                                                        Financial transactions cannot be silently deleted or edited. Reversing this will create an audited offsetting reversal entry and update the customer's balance back.
                                                    </p>
                                                    <div class="bg-light p-3 rounded-2 border mb-3 small">
                                                        <div><strong>Txn Number:</strong> {{ $txn->transaction_number }}</div>
                                                        <div><strong>Customer:</strong> {{ $txn->customer->name ?? 'N/A' }}</div>
                                                        <div><strong>Amount:</strong> ₹ {{ number_format((float) $txn->amount, 2) }} ({{ ucfirst($txn->type) }})</div>
                                                        <div><strong>Date:</strong> {{ $txn->transaction_date->format('d M Y') }}</div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="reason{{ $txn->id }}" class="form-label fw-semibold small">
                                                            Reason for Reversal <span class="text-danger">*</span>
                                                        </label>
                                                        <textarea class="form-control" id="reason{{ $txn->id }}" name="reason" rows="2" placeholder="e.g. Mistakenly entered wrong customer or duplicate entry" required minlength="5"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-warning fw-semibold">
                                                        <i class="bi bi-check2 me-1"></i> Confirm Reversal
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-receipt display-4 text-secondary opacity-50 mb-3 d-inline-block"></i>
                                <h5 class="fw-bold text-dark">No Transactions Found</h5>
                                <p class="small mb-3">No credit or payment entries matched your current filter criteria.</p>
                                <a href="{{ route('transactions.credit') }}" class="btn btn-danger btn-sm me-2">
                                    <i class="bi bi-arrow-up-right-circle me-1"></i> + Give Credit (Udhaar)
                                </a>
                                <a href="{{ route('transactions.payment') }}" class="btn btn-success btn-sm">
                                    <i class="bi bi-arrow-down-left-circle me-1"></i> + Receive Payment (Jama)
                                </a>
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
                Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} entries
            </span>
            <div>
                {{ $transactions->links() }}
            </div>
        </div>
    @endif
</div>
@endsection

