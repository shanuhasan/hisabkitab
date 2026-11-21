@extends('layouts.guest')

@section('title', 'Shopkeeper Login — HisabKitab')

@section('content')
<div class="row justify-content-center py-5">
    <div class="col-md-7 col-lg-5">
        <div class="card card-hk border-0 shadow-sm p-3 p-md-4">
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-primary-subtle text-primary p-3 d-inline-flex align-items-center justify-content-center mb-2" style="width: 56px; height: 56px;">
                        <i class="bi bi-box-arrow-in-right fs-3"></i>
                    </div>
                    <h2 class="h4 fw-bold text-slate-900 mb-1">Shopkeeper Login</h2>
                    <p class="text-muted small">Access your digital udhaar & jama khata</p>
                </div>

                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    <!-- Mobile or Email -->
                    <div class="mb-3">
                        <label for="login" class="form-label fw-semibold small">Mobile Number or Email <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-person-badge"></i></span>
                            <input type="text" class="form-control @error('login') is-invalid @enderror" id="login" name="login" value="{{ old('login') }}" placeholder="10-digit mobile or email" required autofocus>
                        </div>
                        @error('login')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label fw-semibold small mb-0">Password <span class="text-danger">*</span></label>
                            <a href="{{ route('password.request') }}" class="small text-muted text-decoration-none">Forgot Password?</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label small text-muted" for="remember">
                            Remember this device
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Log In to Khata
                    </button>
                </form>

                <div class="text-center mt-4 pt-2 border-top">
                    <p class="small text-muted mb-0">
                        New shopkeeper? 
                        <a href="{{ route('register') }}" class="text-primary fw-semibold text-decoration-none">Open Free Digital Khata</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

