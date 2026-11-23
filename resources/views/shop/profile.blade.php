@extends('layouts.app')

@section('title', 'Shop Profile & Settings — UdhaarHisab')
@section('header', 'Shop Settings & Profile')
@section('subheader', 'Manage your business identity, store address, contact details, and branding')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card card-hk mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-shop me-2 text-primary"></i> Business Information</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle text-uppercase">
                    {{ $tenant->status }}
                </span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('shop.profile.update') }}" enctype="multipart/form-data" novalidate>
                    @csrf

                    <!-- Logo and Brand Row -->
                    <div class="d-flex align-items-center gap-4 mb-4 pb-3 border-bottom">
                        <div class="position-relative">
                            @if ($tenant->logo_path)
                                <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="{{ $tenant->name }}" class="rounded-circle border p-1 shadow-sm object-fit-cover" style="width: 80px; height: 80px;">
                            @else
                                <div class="rounded-circle bg-primary-subtle text-primary border p-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px;">
                                    <i class="bi bi-shop fs-1"></i>
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <label for="logo" class="form-label fw-semibold small mb-1">Store Logo / Brand Image</label>
                            <input class="form-control form-control-sm @error('logo') is-invalid @enderror" type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
                            <div class="form-text small text-muted">Recommended: Square PNG/JPG, max 2MB. Displayed on PDF customer statements.</div>
                            @error('logo')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Shop Name -->
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold small">Shop / Business Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $tenant->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Owner Full Name -->
                        <div class="col-md-6">
                            <label for="owner_name" class="form-label fw-semibold small">Owner Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('owner_name') is-invalid @enderror" id="owner_name" name="owner_name" value="{{ old('owner_name', $tenant->owner_name) }}" required>
                            @error('owner_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Mobile Number -->
                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-semibold small">Mobile Number (WhatsApp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">+91</span>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $tenant->phone) }}" maxlength="10" required>
                            </div>
                            @error('phone')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold small">Email Address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $tenant->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- GSTIN -->
                        <div class="col-md-6">
                            <label for="gstin" class="form-label fw-semibold small">GSTIN (Optional)</label>
                            <input type="text" class="form-control text-uppercase @error('gstin') is-invalid @enderror" id="gstin" name="gstin" value="{{ old('gstin', $tenant->gstin) }}" placeholder="e.g. 07AAAAA0000A1Z5" maxlength="15">
                            @error('gstin')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Currency -->
                        <div class="col-md-6">
                            <label for="currency" class="form-label fw-semibold small">Currency <span class="text-danger">*</span></label>
                            <select class="form-select @error('currency') is-invalid @enderror" id="currency" name="currency">
                                <option value="INR" {{ old('currency', $tenant->currency) === 'INR' ? 'selected' : '' }}>₹ INR (Indian Rupee)</option>
                            </select>
                            @error('currency')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Physical Address -->
                        <div class="col-12">
                            <label for="address" class="form-label fw-semibold small">Store Address</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3" placeholder="Shop No., Market / Area, City, State, PIN code">{{ old('address', $tenant->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                            <i class="bi bi-save2 me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tenant Identity Card -->
    <div class="col-lg-4">
        <div class="card card-hk mb-4">
            <div class="card-header fw-bold">
                <i class="bi bi-shield-check text-success me-2"></i> Tenant Identity
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Tenant ID</span>
                        <span class="fw-bold">#{{ $tenant->id }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Shop Slug</span>
                        <span class="font-monospace text-truncate" style="max-width: 160px;">{{ $tenant->slug }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Account Status</span>
                        @if ($tenant->status === 'active')
                            <span class="badge bg-success">Active</span>
                        @elseif ($tenant->status === 'suspended')
                            <span class="badge bg-danger">Suspended</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($tenant->status) }}</span>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Joined Platform</span>
                        <span>{{ $tenant->created_at->format('d M Y') }}</span>
                    </li>
                </ul>

                <div class="alert alert-light border small text-muted mt-3 mb-0">
                    <i class="bi bi-info-circle-fill text-primary me-1"></i>
                    All customer ledgers, transactions, and reports are strictly scoped to this Tenant ID.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

