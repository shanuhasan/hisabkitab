@extends('layouts.app')

@section('title', 'Edit Plan ' . $plan->name . ' — Super Admin')
@section('header', 'Edit Subscription Plan: ' . $plan->name)
@section('subheader', 'Update pricing, customer quotas, duration, and feature permissions')

@section('header-actions')
<a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary btn-sm">
    &larr; Back to Plans
</a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-hk shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-sliders me-2 text-primary"></i> Plan Parameters &amp; Feature Flags
                </h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.plans.update', $plan) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold small text-muted">Plan Name</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $plan->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="price" class="form-label fw-semibold small text-muted">Price (₹ INR)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" min="0" name="price" id="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $plan->price) }}" required>
                            </div>
                            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="duration_days" class="form-label fw-semibold small text-muted">Duration (Days)</label>
                            <input type="number" min="1" name="duration_days" id="duration_days" class="form-control @error('duration_days') is-invalid @enderror" value="{{ old('duration_days', $plan->duration_days) }}" required>
                            <small class="text-muted" style="font-size: 0.75rem;">e.g., 30 for monthly, 365 for annual</small>
                            @error('duration_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="customer_limit" class="form-label fw-semibold small text-muted">Customer Limit</label>
                            <input type="number" name="customer_limit" id="customer_limit" class="form-control @error('customer_limit') is-invalid @enderror" value="{{ old('customer_limit', $plan->customer_limit) }}" required>
                            <small class="text-muted" style="font-size: 0.75rem;">Enter -1 for Unlimited customers</small>
                            @error('customer_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold small text-muted">Description / Marketing Tagline</label>
                            <textarea name="description" id="description" class="form-control" rows="2">{{ old('description', $plan->description) }}</textarea>
                        </div>

                        <div class="col-12">
                            <hr class="my-2">
                            <label class="form-label fw-bold text-dark d-block">Feature Permissions &amp; Entitlements</label>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="features[pdf_statements]" value="1" id="featurePdf" {{ $plan->hasFeature('pdf_statements') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold small" for="featurePdf">
                                    Downloadable PDF Statements &amp; Receipts
                                </label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="features[whatsapp_reminders]" value="1" id="featureWa" {{ $plan->hasFeature('whatsapp_reminders') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold small" for="featureWa">
                                    WhatsApp Click-to-Chat Reminders
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="features[csv_exports]" value="1" id="featureCsv" {{ $plan->hasFeature('csv_exports') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold small" for="featureCsv">
                                    Excel / CSV Ledger Data Exports
                                </label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="features[advanced_reports]" value="1" id="featureReports" {{ $plan->hasFeature('advanced_reports') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold small" for="featureReports">
                                    Advanced Analytics &amp; Aging Reports
                                </label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="features[priority_support]" value="1" id="featureSupport" {{ $plan->hasFeature('priority_support') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold small" for="featureSupport">
                                    Priority Support Access
                                </label>
                            </div>
                        </div>

                        <div class="col-12 mt-4 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" {{ $plan->is_active ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold small" for="isActive">
                                    Plan is Active &amp; Available for Purchase
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary fw-bold px-4">
                                <i class="bi bi-check-lg me-1"></i> Save Plan Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

