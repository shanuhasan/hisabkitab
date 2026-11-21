@extends('layouts.guest')

@section('title', 'Set New Password — HisabKitab')

@section('content')
<div class="row justify-content-center py-5">
    <div class="col-md-7 col-lg-5">
        <div class="card card-hk border-0 shadow-sm p-3 p-md-4">
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-success-subtle text-success p-3 d-inline-flex align-items-center justify-content-center mb-2" style="width: 56px; height: 56px;">
                        <i class="bi bi-shield-lock fs-3"></i>
                    </div>
                    <h2 class="h4 fw-bold text-slate-900 mb-1">Set New Password</h2>
                    <p class="text-muted small">Choose a strong password for your account.</p>
                </div>

                <form method="POST" action="{{ route('password.update') }}" novalidate>
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="mb-3">
                        <label for="identifier" class="form-label fw-semibold small">Account Identifier</label>
                        <input type="text" class="form-control bg-light" id="identifier" name="identifier" value="{{ $identifier ?? old('identifier') }}" readonly required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold small">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="At least 8 characters" required autofocus>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label fw-semibold small">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-shield-check"></i></span>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Repeat your new password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Update Password & Log In
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

