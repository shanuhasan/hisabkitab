@extends('layouts.app')

@section('title', 'Create Subscription Plan — Super Admin')
@section('header', 'Create New Subscription Plan')
@section('subheader', 'Define package pricing, validity duration, customer quotas, and features')

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
                    <i class="bi bi-plus-circle me-2 text-primary"></i> New SaaS Plan Configuration
                </h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.plans.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold small text-muted">Plan Name</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Enterprise Plan" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="slug" class="form-label fw-semibold small text-muted">Slug / Unique Key</label>
                            <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" placeholder="e.g. enterprise">
                            <small class="text-muted" style="font-size: 0.75rem;">Leave blank to auto-generate from name</small>
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="price" class="form-label fw-semibold small text-muted">Price (₹ INR)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" min="0" name="price" id="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', '299.00') }}" required>
                            </div>
                            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="duration_days" class="form-label fw-semibold small text-muted">Duration (Days)</label>
                            <input type="number" min="1" name="duration_days" id="duration_days" class="form-control @error('duration_days') is-invalid @enderror" value="{{ old('duration_days', '30') }}" required>
                            <small class="text-muted" style="font-size: 0.75rem;">e.g., 30 for monthly, 365 for annual</small>
                            @error('duration_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="customer_limit" class="form-label fw-semibold small text-muted">Customer Limit</label>
                            <input type="number" name="customer_limit" id="customer_limit" class="form-control @error('customer_limit') is-invalid @enderror" value="{{ old('customer_limit', '500') }}" required>
                            <small class="text-muted" style="font-size: 0.75rem;">Enter -1 for Unlimited customers</small>
                            @error('customer_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Initial Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" checked>
                                <label class="form-check-label fw-semibold small" for="isActive">
                                    Enable Plan Immediately
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold small text-muted">Marketing Description</label>
                            <textarea name="description" id="description" class="form-control" rows="2" placeholder="Brief summary of who this plan is designed for...">{{ old('description') }}</textarea>
                        </div>

                        <div class="col-12">
                            <hr class="my-2">
                            <label class="form-label fw-bold text-dark d-block">Feature Flags &amp; Entitlements</label>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="features[pdf_statements]" value="1" id="featurePdf" checked>
                                <label class="form-check-label fw-semibold small" for="featurePdf">
                                    Downloadable PDF Statements &amp; Receipts
                                </label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="features[whatsapp_reminders]" value="1" id="featureWa" checked>
                                <label class="form-check-label fw-semibold small" for="featureWa">
                                    WhatsApp Click-to-Chat Reminders
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="features[csv_exports]" value="1" id="featureCsv" checked>
                                <label class="form-check-label fw-semibold small" for="featureCsv">
                                    Excel / CSV Ledger Data Exports
                                </label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="features[advanced_reports]" value="1" id="featureReports" checked>
                                <label class="form-check-label fw-semibold small" for="featureReports">
                                    Advanced Analytics &amp; Aging Reports
                                </label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="features[priority_support]" value="1" id="featureSupport">
                                <label class="form-check-label fw-semibold small" for="featureSupport">
                                    Priority Support Access
                                </label>
                            </div>
                        </div>

                        <div class="col-12 mt-4 text-end">
                            <button type="submit" class="btn btn-primary fw-bold px-4">
                                <i class="bi bi-check-lg me-1"></i> Create Subscription Plan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

