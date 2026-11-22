@extends('layouts.app')

@section('title', 'Platform Settings — Super Admin')
@section('header', 'Global Platform Settings')
@section('subheader', 'Configure system-wide brand identity, contact information, default currency, and operational timezone')

@section('header-actions')
<a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
    &larr; Admin Dashboard
</a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-hk shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-gear-fill me-2 text-primary"></i> Application Configuration Parameters
                </h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.settings.update') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="site_name" class="form-label fw-semibold small text-muted">Platform / Website Name</label>
                            <input type="text" name="site_name" id="site_name" class="form-control @error('site_name') is-invalid @enderror" value="{{ old('site_name', $settings['site_name']) }}" required>
                            @error('site_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="contact_email" class="form-label fw-semibold small text-muted">Support Email Address</label>
                            <input type="email" name="contact_email" id="contact_email" class="form-control @error('contact_email') is-invalid @enderror" value="{{ old('contact_email', $settings['contact_email']) }}" required>
                            @error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="contact_phone" class="form-label fw-semibold small text-muted">Support Helpline Phone</label>
                            <input type="text" name="contact_phone" id="contact_phone" class="form-control @error('contact_phone') is-invalid @enderror" value="{{ old('contact_phone', $settings['contact_phone']) }}" required>
                            @error('contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="currency" class="form-label fw-semibold small text-muted">Default Currency</label>
                            <input type="text" name="currency" id="currency" class="form-control @error('currency') is-invalid @enderror" value="{{ old('currency', $settings['currency']) }}" required>
                            <small class="text-muted" style="font-size: 0.75rem;">Standard ISO currency code (e.g. INR)</small>
                            @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="timezone" class="form-label fw-semibold small text-muted">Default Timezone</label>
                            <input type="text" name="timezone" id="timezone" class="form-control @error('timezone') is-invalid @enderror" value="{{ old('timezone', $settings['timezone']) }}" required>
                            <small class="text-muted" style="font-size: 0.75rem;">e.g. Asia/Kolkata</small>
                            @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="trial_days" class="form-label fw-semibold small text-muted">New Merchant Free Trial Duration</label>
                            <div class="input-group">
                                <input type="number" min="1" max="365" name="trial_days" id="trial_days" class="form-control @error('trial_days') is-invalid @enderror" value="{{ old('trial_days', $settings['trial_days']) }}" required>
                                <span class="input-group-text">Days</span>
                            </div>
                            @error('trial_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="support_hours" class="form-label fw-semibold small text-muted">Helpline Working Hours</label>
                            <input type="text" name="support_hours" id="support_hours" class="form-control" value="{{ old('support_hours', $settings['support_hours']) }}" placeholder="e.g. Monday to Saturday 9:00 AM - 8:00 PM IST">
                        </div>

                        <div class="col-12 mt-4 text-end">
                            <button type="submit" class="btn btn-primary fw-bold px-4">
                                <i class="bi bi-save me-1"></i> Save Platform Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

