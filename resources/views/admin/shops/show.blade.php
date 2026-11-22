@extends('layouts.app')

@section('title', 'Shop ' . $shop->name . ' — Super Admin')
@section('header', 'Shop Overview: ' . $shop->name)
@section('subheader', 'Merchant ID: #' . $shop->id . ' • Registered on ' . $shop->created_at->format('d M Y'))

@section('header-actions')
<div class="d-flex gap-2">
    <form action="{{ route('admin.shops.toggle_status', $shop) }}" method="POST" onsubmit="return confirm('Confirm: {{ $shop->status === 'active' ? 'Suspend' : 'Activate' }} {{ $shop->name }}?');">
        @csrf
        <button type="submit" class="btn {{ $shop->status === 'active' ? 'btn-danger' : 'btn-success' }} btn-sm fw-semibold shadow-sm">
            <i class="bi {{ $shop->status === 'active' ? 'bi-lock-fill' : 'bi-unlock-fill' }} me-1"></i>
            {{ $shop->status === 'active' ? 'Suspend Shop Account' : 'Activate Shop Account' }}
        </button>
    </form>
    <a href="{{ route('admin.shops.index') }}" class="btn btn-outline-secondary btn-sm">
        &larr; All Shops
    </a>
</div>
@endsection

@section('content')
<!-- Top Profile Cards -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card card-hk h-100 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-info-circle me-2 text-primary"></i> Business &amp; Owner Profile
                </h6>
                <span class="badge {{ $shop->status === 'active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }} px-3 py-1 fs-6">
                    {{ ucfirst($shop->status) }}
                </span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold d-block">Shop / Business Name</label>
                        <span class="fw-bold text-dark fs-5">{{ $shop->name }}</span>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold d-block">Shop Owner</label>
                        <span class="fw-bold text-dark fs-5">{{ $shop->owner_name }}</span>
                    </div>

                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold d-block">Owner Mobile Number</label>
                        <span class="font-monospace text-dark">+91 {{ $shop->phone }}</span>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold d-block">Owner Email</label>
                        <span class="text-dark">{{ $shop->email ?? 'Not provided' }}</span>
                    </div>

                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold d-block">GSTIN Number</label>
                        <span class="font-monospace text-dark">{{ $shop->gstin ?? 'Not registered' }}</span>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold d-block">Default Currency</label>
                        <span class="badge bg-light text-dark border">{{ $shop->currency ?? 'INR' }}</span>
                    </div>

                    <div class="col-12">
                        <label class="text-muted small fw-semibold d-block">Business Address</label>
                        <span class="text-dark">{{ $shop->address ?? 'No physical address specified' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Shop Activity & Metric Summary -->
    <div class="col-lg-4">
        <div class="card card-hk h-100 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-activity me-2 text-primary"></i> Tenant Data Metrics
                </h6>
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-around">
                <div class="border-bottom pb-3">
                    <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Customers Managed</span>
                    <h3 class="fw-bold text-primary mb-0">{{ $customerCount }} Grahak</h3>
                    <small class="text-muted">Total customer records created</small>
                </div>

                <div class="border-bottom py-3">
                    <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Total Ledger Transactions</span>
                    <h3 class="fw-bold text-success mb-0">{{ $transactionCount }} Entries</h3>
                    <small class="text-muted">Udhaar &amp; Jama operations logged</small>
                </div>

                <div class="pt-3">
                    <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Active SaaS Plan</span>
                    @if ($currentSubscription && $currentSubscription->plan)
                        <h4 class="fw-bold text-dark mb-0">{{ $currentSubscription->plan->name }}</h4>
                        <small class="text-muted">Expires: {{ $currentSubscription->ends_at ? $currentSubscription->ends_at->format('d M Y') : 'N/A' }}</small>
                    @else
                        <span class="badge bg-secondary">No Active Subscription</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Subscription History -->
<div class="card card-hk shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-credit-card-2-front me-2 text-primary"></i> Shop Subscription History
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4" style="width: 25%;">Plan Name</th>
                    <th style="width: 20%;">Start Date</th>
                    <th style="width: 20%;">End Date</th>
                    <th style="width: 15%;">Status</th>
                    <th class="pe-4" style="width: 20%;">Payment Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shop->subscriptions as $sub)
                    <tr>
                        <td class="ps-4">
                            <strong class="text-dark">{{ $sub->plan ? $sub->plan->name : 'Custom Plan' }}</strong>
                            <br><small class="text-muted">₹ {{ number_format((float) ($sub->plan->price ?? 0), 2) }}</small>
                        </td>
                        <td>{{ $sub->starts_at ? $sub->starts_at->format('d M Y') : 'N/A' }}</td>
                        <td>{{ $sub->ends_at ? $sub->ends_at->format('d M Y') : 'Never' }}</td>
                        <td>
                            <span class="badge {{ $sub->statusBadgeClass() }} px-2 py-1">
                                {{ ucfirst($sub->status) }}
                            </span>
                        </td>
                        <td class="pe-4 small text-muted">
                            @if ($sub->payment_details)
                                <code style="font-size: 0.75rem;">{{ json_encode($sub->payment_details) }}</code>
                            @else
                                Free / Manual Activation
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted small">No subscriptions recorded for this shop.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

