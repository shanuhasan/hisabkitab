@extends('layouts.app')

@section('title', 'Edit Customer — ' . $customer->name)
@section('header', 'Edit Customer: ' . $customer->name)
@section('subheader', 'Update contact information and credit limit')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-hk shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i> Edit Customer Information</span>
                <span class="badge bg-light text-dark border">ID: #{{ $customer->id }}</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('customers.update', $customer) }}" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <!-- Customer Name -->
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold small">Customer Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $customer->name) }}" required autofocus>
                            </div>
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Mobile Number -->
                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-semibold small">Mobile Number (WhatsApp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">+91</span>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" maxlength="10" required>
                            </div>
                            @error('phone')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold small">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $customer->email) }}">
                            </div>
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Credit Limit -->
                        <div class="col-md-6">
                            <label for="credit_limit" class="form-label fw-semibold small">Credit Limit (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">₹</span>
                                <input type="number" step="0.01" class="form-control @error('credit_limit') is-invalid @enderror" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit) }}">
                            </div>
                            @error('credit_limit')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Address -->
                        <div class="col-12">
                            <label for="address" class="form-label fw-semibold small">Address / Landmark</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address', $customer->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Private Notes -->
                        <div class="col-12">
                            <label for="notes" class="form-label fw-semibold small">Private Notes / Remarks</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="2">{{ old('notes', $customer->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Financial Integrity Alert -->
                        <div class="col-12">
                            <div class="alert alert-light border small text-muted mb-0">
                                <i class="bi bi-shield-lock-fill text-primary me-1"></i>
                                <strong>Financial Integrity Notice:</strong> The customer's current balance (₹ {{ number_format((float) $customer->current_balance, 2) }}) is calculated automatically from audited transaction records and cannot be directly modified here.
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                        <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary">
                            &larr; Back to Details
                        </a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                            <i class="bi bi-save2 me-1"></i> Update Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

