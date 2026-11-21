@extends('layouts.guest')

@section('title', 'Forgot Password — HisabKitab')

@section('content')
<div class="row justify-content-center py-5">
    <div class="col-md-7 col-lg-5">
        <div class="card card-hk border-0 shadow-sm p-3 p-md-4">
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-warning-subtle text-warning p-3 d-inline-flex align-items-center justify-content-center mb-2" style="width: 56px; height: 56px;">
                        <i class="bi bi-key fs-3"></i>
                    </div>
                    <h2 class="h4 fw-bold text-slate-900 mb-1">Reset Password</h2>
                    <p class="text-muted small">Enter your registered mobile or email to recover your account.</p>
                </div>

                <form method="POST" action="{{ route('password.email') }}" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="login" class="form-label fw-semibold small">Registered Mobile or Email <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-person-badge"></i></span>
                            <input type="text" class="form-control @error('login') is-invalid @enderror" id="login" name="login" value="{{ old('login') }}" placeholder="Mobile or email" required autofocus>
                        </div>
                        @error('login')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-arrow-right-circle me-1"></i> Proceed to Reset
                    </button>
                </form>

                <div class="text-center mt-4 pt-2 border-top">
                    <a href="{{ route('login') }}" class="small text-muted text-decoration-none">
                        &larr; Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

