@extends('layouts.guest')

@section('title', 'Shopkeeper Registration — UdhaarHisab')

@section('content')
<div class="row justify-content-center py-4">
    <div class="col-md-8 col-lg-6">
        <div class="card card-hk border-0 shadow-sm p-3 p-md-4">
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-primary-subtle text-primary p-3 d-inline-flex align-items-center justify-content-center mb-2" style="width: 56px; height: 56px;">
                        <i class="bi bi-shop fs-3"></i>
                    </div>
                    <h2 class="h4 fw-bold text-slate-900 mb-1">Create Shop Account</h2>
                    <p class="text-muted small">Register your shop in 1 minute. Free 30-day trial included.</p>
                </div>

                <form method="POST" action="{{ route('register') }}" novalidate>
                    @csrf

                    <!-- Owner Full Name -->
                    <div class="mb-3">
                        <label for="owner_name" class="form-label fw-semibold small">Owner Full Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control @error('owner_name') is-invalid @enderror" id="owner_name" name="owner_name" value="{{ old('owner_name') }}" placeholder="e.g. Ramesh Kumar" required autofocus>
                        </div>
                        @error('owner_name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Shop / Business Name -->
                    <div class="mb-3">
                        <label for="shop_name" class="form-label fw-semibold small">Shop / Business Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-building"></i></span>
                            <input type="text" class="form-control @error('shop_name') is-invalid @enderror" id="shop_name" name="shop_name" value="{{ old('shop_name') }}" placeholder="e.g. Ramesh Kirana & General Store" required>
                        </div>
                        @error('shop_name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Mobile Number -->
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-semibold small">Mobile Number (WhatsApp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">+91</span>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="10-digit mobile (e.g. 9876543210)" maxlength="10" required>
                        </div>
                        <div class="form-text small text-muted">You can log into your account using this mobile number.</div>
                        @error('phone')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Optional Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold small">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="owner@example.com">
                        </div>
                        @error('email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold small">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="At least 8 characters" required>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label fw-semibold small">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Repeat your password" required>
                        </div>
                    </div>

                    <!-- Terms Checkbox -->
                    <div class="form-check mb-4">
                        <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" name="terms" id="terms" {{ old('terms') ? 'checked' : '' }} required>
                        <label class="form-check-label small text-muted" for="terms">
                            I agree to the Terms of Service & Privacy Policy for UdhaarHisab.
                        </label>
                        @error('terms')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-check-circle-fill me-1"></i> Register Shop (Start Free Trial)
                    </button>
                </form>

                <div class="text-center mt-4 pt-2 border-top">
                    <p class="small text-muted mb-0">
                        Already have a shop account? 
                        <a href="{{ route('login') }}" class="text-primary fw-semibold text-decoration-none">Log in here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

