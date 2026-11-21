@extends('layouts.app')

@section('title', 'Udhaar Dena (Give Credit) — HisabKitab')
@section('header', 'Udhaar Dena (Gave Credit to Customer)')
@section('subheader', 'Record items given on credit or debit entry in customer ledger')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-hk shadow-sm border-top border-danger border-4">
            <div class="card-header bg-white py-3">
                <span class="fw-bold text-danger">
                    <i class="bi bi-arrow-up-right-circle-fill me-2"></i> + Udhaar Entry (You Gave)
                </span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('transactions.store') }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    <input type="hidden" name="type" value="credit">

                    <div class="row g-3">
                        <!-- Select Customer -->
                        <div class="col-12">
                            <label for="customer_id" class="form-label fw-semibold small">Select Customer (Grahak) <span class="text-danger">*</span></label>
                            <select class="form-select @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                                <option value="">-- Choose a customer --</option>
                                @foreach ($customers as $cust)
                                    <option value="{{ $cust->id }}" {{ (old('customer_id', $selectedCustomer->id ?? '') == $cust->id) ? 'selected' : '' }}>
                                        {{ $cust->name }} (+91 {{ $cust->phone }}) — Current Balance: ₹ {{ number_format((float) $cust->current_balance, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">
                                Need to add a new customer first? 
                                <a href="{{ route('customers.create') }}" class="text-primary text-decoration-none">Add Customer Here</a>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <label for="amount" class="form-label fw-semibold small">Udhaar Amount (₹) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-danger fw-bold">₹</span>
                                <input type="number" step="0.01" class="form-control form-control-lg fw-bold text-danger @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount') }}" placeholder="0.00" required autofocus>
                            </div>
                            @error('amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Transaction Date -->
                        <div class="col-md-6">
                            <label for="transaction_date" class="form-label fw-semibold small">Transaction Date <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-calendar"></i></span>
                                <input type="date" class="form-control form-control-lg @error('transaction_date') is-invalid @enderror" id="transaction_date" name="transaction_date" value="{{ old('transaction_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                            </div>
                            @error('transaction_date')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Reference Number (Optional Bill No.) -->
                        <div class="col-md-6">
                            <label for="reference_number" class="form-label fw-semibold small">Bill / Invoice No. <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="text" class="form-control @error('reference_number') is-invalid @enderror" id="reference_number" name="reference_number" value="{{ old('reference_number') }}" placeholder="e.g. Bill #104">
                            @error('reference_number')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Bill / Slip Photo -->
                        <div class="col-md-6">
                            <label for="bill_image" class="form-label fw-semibold small">Attach Bill / Slip Photo <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="file" class="form-control @error('bill_image') is-invalid @enderror" id="bill_image" name="bill_image" accept="image/*">
                            <div class="form-text small text-muted">Max size: 4MB (PNG, JPG, WEBP).</div>
                            @error('bill_image')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description / Item Remarks -->
                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold small">Description / Items Given <span class="text-muted fw-normal">(Optional)</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2" placeholder="e.g. 5kg Atta, 1L Mustard Oil, Soap">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                        <a href="{{ $selectedCustomer ? route('customers.show', $selectedCustomer) : route('customers.index') }}" class="btn btn-outline-secondary">
                            &larr; Cancel
                        </a>
                        <button type="submit" class="btn btn-danger px-4 py-2 fw-semibold shadow-sm">
                            <i class="bi bi-check-circle-fill me-1"></i> Save Udhaar Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

