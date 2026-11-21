@extends('layouts.app')

@section('title', 'Transaction ' . $transaction->transaction_number . ' — HisabKitab')
@section('header', 'Transaction: ' . $transaction->transaction_number)
@section('subheader', 'Recorded on ' . $transaction->transaction_date->format('d M Y'))

@section('header-actions')
<div class="d-flex flex-wrap gap-2">
    @if ($transaction->type === 'payment')
        <a href="{{ route('transactions.receipt.pdf', $transaction) }}" class="btn btn-outline-danger btn-sm fw-semibold" title="Download PDF Receipt">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF Receipt
        </a>
        <a href="{{ route('transactions.whatsapp.receipt', $transaction) }}" target="_blank" class="btn btn-success btn-sm fw-semibold" title="Share Receipt on WhatsApp">
            <i class="bi bi-whatsapp me-1"></i> Share via WhatsApp
        </a>
    @endif
    @if ($transaction->customer)
        <a href="{{ route('customers.show', $transaction->customer) }}" class="btn btn-outline-primary btn-sm fw-semibold">
            <i class="bi bi-person me-1"></i> Customer Ledger
        </a>
    @endif
    <a href="{{ route('transactions.history') }}" class="btn btn-outline-secondary btn-sm">
        &larr; All Transactions
    </a>
</div>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Reversal Alert Banner -->
        @if ($transaction->is_reversed)
            <div class="alert alert-warning border shadow-sm mb-4">
                <div class="d-flex align-items-center mb-1">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                    <h6 class="fw-bold mb-0">This transaction has been reversed.</h6>
                </div>
                <p class="small mb-0">
                    An offsetting reversal transaction was created to cancel out this entry.
                    @if ($transaction->reversal)
                        <br><strong>Reason:</strong> {{ $transaction->reversal->reason }}
                        <br><strong>Reversed on:</strong> {{ $transaction->reversal->created_at->format('d M Y, h:i A') }}
                    @endif
                </p>
            </div>
        @endif

        <div class="card card-hk shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold">
                    <i class="bi bi-receipt me-2 text-primary"></i> Transaction Summary
                </span>
                <span class="badge {{ $transaction->type === 'credit' ? 'badge-credit' : ($transaction->type === 'payment' ? 'badge-payment' : 'bg-secondary') }} fs-6">
                    {{ $transaction->type === 'credit' ? 'Udhaar Dena (+)' : ($transaction->type === 'payment' ? 'Jama Lena (-)' : 'Reversal') }}
                </span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold d-block">Transaction Amount</label>
                        <div class="{{ $transaction->type === 'credit' ? 'amount-credit' : ($transaction->type === 'payment' ? 'amount-payment' : 'text-secondary') }} display-6 fw-bold">
                            ₹ {{ number_format((float) $transaction->amount, 2) }}
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold d-block">Customer Running Balance After Entry</label>
                        <div class="h3 fw-bold {{ $transaction->running_balance > 0 ? 'text-danger' : ($transaction->running_balance < 0 ? 'text-success' : 'text-slate-800') }}">
                            ₹ {{ number_format((float) $transaction->running_balance, 2) }}
                        </div>
                    </div>

                    <div class="col-12"><hr class="my-2"></div>

                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold d-block">Customer Name</label>
                        <span class="fw-bold">{{ $transaction->customer->name ?? 'N/A' }}</span>
                    </div>

                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold d-block">Customer Phone</label>
                        <span>+91 {{ $transaction->customer->phone ?? 'N/A' }}</span>
                    </div>

                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold d-block">Transaction Reference</label>
                        <span class="font-monospace fw-bold">{{ $transaction->transaction_number }}</span>
                    </div>

                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold d-block">Bill / Invoice Number</label>
                        <span>{{ $transaction->reference_number ?? 'Not specified' }}</span>
                    </div>

                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold d-block">Payment Method</label>
                        <span class="badge bg-light text-dark border">{{ ucfirst($transaction->payment_mode) }}</span>
                    </div>

                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold d-block">Recorded By</label>
                        <span>{{ $transaction->user->name ?? 'Staff' }}</span>
                    </div>

                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold d-block">Transaction Date</label>
                        <span>{{ $transaction->transaction_date->format('d M Y') }}</span>
                    </div>

                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold d-block">Timestamp Recorded</label>
                        <span class="text-muted small">{{ $transaction->created_at->format('d M Y, h:i A') }}</span>
                    </div>

                    <div class="col-12">
                        <label class="text-muted small fw-semibold d-block">Description / Remarks</label>
                        <div class="bg-light p-3 rounded border small text-dark">
                            {{ $transaction->description ?: 'No remarks provided.' }}
                        </div>
                    </div>

                    <!-- Bill Image Attachment -->
                    @if ($transaction->bill_image_path)
                        <div class="col-12 mt-3">
                            <label class="text-muted small fw-semibold d-block mb-2">Attached Bill / Receipt Photo</label>
                            <div class="border rounded p-2 d-inline-block bg-white shadow-sm">
                                <a href="{{ asset('storage/' . $transaction->bill_image_path) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $transaction->bill_image_path) }}" alt="Receipt" class="img-fluid rounded" style="max-height: 280px;">
                                </a>
                                <div class="text-center small mt-1 text-muted">Click image to view full size</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

