@extends('layouts.app')

@section('title', 'Jama Lena (Receive Payment) — UdhaarHisab')
@section('header', 'Jama Lena (Received Payment from Customer)')
@section('subheader', 'Record payments received in cash, UPI, bank transfer or other modes')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-hk shadow-sm border-top border-success border-4">
            <div class="card-header bg-white py-3">
                <span class="fw-bold text-success">
                    <i class="bi bi-arrow-down-left-circle-fill me-2"></i> + Jama Entry (You Received)
                </span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('transactions.store') }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    <input type="hidden" name="type" value="payment">

                    <div class="row g-3">
                        <!-- Select Customer -->
                        <div class="col-12">
                            <label for="customer_id" class="form-label fw-semibold small">Select Customer (Grahak) <span class="text-danger">*</span></label>
                            <select class="form-select @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                                <option value="">-- Choose a customer --</option>
                                @foreach ($customers as $cust)
                                    <option value="{{ $cust->id }}" {{ (old('customer_id', $selectedCustomer->id ?? '') == $cust->id) ? 'selected' : '' }}>
                                        {{ $cust->name }} (+91 {{ $cust->phone }}) — Current Udhaar: ₹ {{ number_format((float) $cust->current_balance, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Amount Received -->
                        <div class="col-md-6">
                            <label for="amount" class="form-label fw-semibold small">Payment Amount (₹) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-success fw-bold">₹</span>
                                <input type="number" step="0.01" class="form-control form-control-lg fw-bold text-success @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount') }}" placeholder="0.00" required autofocus>
                            </div>
                            @error('amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Payment Mode -->
                        <div class="col-md-6">
                            <label for="payment_mode" class="form-label fw-semibold small">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select form-control-lg @error('payment_mode') is-invalid @enderror" id="payment_mode" name="payment_mode" required>
                                <option value="cash" {{ old('payment_mode') === 'cash' ? 'selected' : '' }}>Cash (Nagad)</option>
                                <option value="upi" {{ old('payment_mode') === 'upi' ? 'selected' : '' }}>UPI / QR Code (GPay, PhonePe, Paytm)</option>
                                <option value="bank_transfer" {{ old('payment_mode') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer / NEFT / IMPS</option>
                                <option value="cheque" {{ old('payment_mode') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                                <option value="other" {{ old('payment_mode') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('payment_mode')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Transaction Date -->
                        <div class="col-md-6">
                            <label for="transaction_date" class="form-label fw-semibold small">Payment Date <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-calendar"></i></span>
                                <input type="date" class="form-control @error('transaction_date') is-invalid @enderror" id="transaction_date" name="transaction_date" value="{{ old('transaction_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                            </div>
                            @error('transaction_date')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Reference Number (Receipt or UPI Ref) -->
                        <div class="col-md-6">
                            <label for="reference_number" class="form-label fw-semibold small">UPI UTR / Receipt Ref. <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="text" class="form-control @error('reference_number') is-invalid @enderror" id="reference_number" name="reference_number" value="{{ old('reference_number') }}" placeholder="e.g. UPI Ref #4321098765">
                            @error('reference_number')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Allow Advance Checkbox -->
                        <div class="col-12">
                            <div class="form-check bg-light p-3 rounded-2 border">
                                <input class="form-check-input" type="checkbox" name="allow_advance" id="allow_advance" value="1" {{ old('allow_advance') ? 'checked' : '' }}>
                                <label class="form-check-label small fw-semibold text-dark" for="allow_advance">
                                    Allow Advance Payment (Peshi Jama)
                                </label>
                                <div class="form-text small text-muted">
                                    Check this box if the customer is depositing money in advance or paying more than their current outstanding balance.
                                </div>
                            </div>
                        </div>

                        <!-- Notes / Remarks -->
                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold small">Notes / Remarks <span class="text-muted fw-normal">(Optional)</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2" placeholder="e.g. Cash handed by customer son, or GPay screenshot verified">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Payment Receipt Photo -->
                        <div class="col-12">
                            <label for="bill_image" class="form-label fw-semibold small">Attach Receipt / UPI Screenshot <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="file" class="form-control @error('bill_image') is-invalid @enderror" id="bill_image" name="bill_image" accept="image/*">
                            @error('bill_image')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                        <a href="{{ $selectedCustomer ? route('customers.show', $selectedCustomer) : route('customers.index') }}" class="btn btn-outline-secondary">
                            &larr; Cancel
                        </a>
                        <button type="submit" class="btn btn-success px-4 py-2 fw-semibold shadow-sm">
                            <i class="bi bi-check-circle-fill me-1"></i> Save Payment Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

