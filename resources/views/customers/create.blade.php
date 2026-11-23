@extends('layouts.app')

@section('title', 'Add Customer — UdhaarHisab')
@section('header', 'Add Customer (Naya Grahak Jodein)')
@section('subheader', 'Create a new customer credit ledger account for your shop')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-hk shadow-sm">
            <div class="card-header bg-white py-3">
                <span class="fw-bold"><i class="bi bi-person-plus-fill me-2 text-primary"></i> Customer Information</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('customers.store') }}" novalidate>
                    @csrf

                    <div class="row g-3">
                        <!-- Customer Name -->
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold small">Customer Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Mukesh Sharma" required autofocus>
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
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="10-digit mobile number" maxlength="10" required>
                            </div>
                            <div class="form-text small text-muted">Used for 1-tap WhatsApp payment reminders.</div>
                            @error('phone')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold small">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="customer@example.com">
                            </div>
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Credit Limit -->
                        <div class="col-md-6">
                            <label for="credit_limit" class="form-label fw-semibold small">Credit Limit (₹) <span class="text-muted fw-normal">(Optional)</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">₹</span>
                                <input type="number" step="0.01" class="form-control @error('credit_limit') is-invalid @enderror" id="credit_limit" name="credit_limit" value="{{ old('credit_limit') }}" placeholder="0.00 (leave 0 for no limit)">
                            </div>
                            @error('credit_limit')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Opening Balance Section -->
                        <div class="col-12 mt-4 pt-3 border-top">
                            <div class="bg-light p-3 rounded-3 border">
                                <h6 class="fw-bold mb-2">
                                    <i class="bi bi-wallet2 text-primary me-1"></i> Opening Balance (Purana Baki / Khata Shuruat)
                                </h6>
                                <p class="text-muted small mb-3">If this customer already has an existing pending balance from your paper notebook, enter it here. A proper opening ledger entry will be recorded automatically.</p>

                                <div class="row g-3 align-items-center">
                                    <div class="col-md-6">
                                        <label for="opening_balance" class="form-label fw-semibold small">Opening Amount (₹)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">₹</span>
                                            <input type="number" step="0.01" class="form-control @error('opening_balance') is-invalid @enderror" id="opening_balance" name="opening_balance" value="{{ old('opening_balance') }}" placeholder="0.00">
                                        </div>
                                        @error('opening_balance')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small d-block">Balance Type</label>
                                        <div class="d-flex gap-3 pt-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="opening_balance_type" id="type_credit" value="credit" {{ old('opening_balance_type', 'credit') === 'credit' ? 'checked' : '' }}>
                                                <label class="form-check-label small fw-semibold text-danger" for="type_credit">
                                                    You'll Get (Udhaar Dena)
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="opening_balance_type" id="type_payment" value="payment" {{ old('opening_balance_type') === 'payment' ? 'checked' : '' }}>
                                                <label class="form-check-label small fw-semibold text-success" for="type_payment">
                                                    You'll Give (Peshi Jama)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Address -->
                        <div class="col-12 mt-3">
                            <label for="address" class="form-label fw-semibold small">Address / Landmark <span class="text-muted fw-normal">(Optional)</span></label>
                            <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2" placeholder="House/Shop no., Colony, Village or City">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Private Notes -->
                        <div class="col-12">
                            <label for="notes" class="form-label fw-semibold small">Private Notes / Remarks <span class="text-muted fw-normal">(Optional)</span></label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="2" placeholder="Reference, known by, work profile, etc.">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
                            &larr; Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                            <i class="bi bi-check-circle-fill me-1"></i> Save Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

