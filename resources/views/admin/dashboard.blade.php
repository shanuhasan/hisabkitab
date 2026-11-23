@extends('layouts.app')

@section('title', 'Super Admin Dashboard — UdhaarHisab SaaS')
@section('header', 'Super Admin Command Center')
@section('subheader', 'Global SaaS multi-tenant metrics, subscriber health, revenue and administrative operations')

@section('header-actions')
<div class="d-flex gap-2">
    <a href="{{ route('admin.shops.index') }}" class="btn btn-primary btn-sm fw-semibold shadow-sm">
        <i class="bi bi-shop me-1"></i> Manage Shops
    </a>
    <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-primary btn-sm fw-semibold">
        <i class="bi bi-tags me-1"></i> Subscription Plans
    </a>
    <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-success btn-sm fw-semibold">
        <i class="bi bi-cash-stack me-1"></i> Payments
    </a>
    <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold">
        <i class="bi bi-gear-fill me-1"></i> Platform Settings
    </a>
</div>
@endsection

@section('content')
<!-- Core Platform Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Total Registered Shops -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-hk h-100 border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-semibold text-uppercase">Total Shops</span>
                    <i class="bi bi-shop-window text-primary fs-4"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">{{ $totalShops }}</h3>
                <small class="text-muted">Registered SaaS tenants</small>
            </div>
        </div>
    </div>

    <!-- Active vs Suspended Shops -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-hk h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-semibold text-uppercase">Active Shops</span>
                    <i class="bi bi-check-circle-fill text-success fs-4"></i>
                </div>
                <h3 class="fw-bold text-success mb-1">{{ $activeShops }}</h3>
                <small class="text-muted">
                    <span class="text-danger fw-semibold">{{ $suspendedShops }}</span> suspended accounts
                </small>
            </div>
        </div>
    </div>

    <!-- Monthly Recurring Revenue (MRR) -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-hk h-100 border-start border-4 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-semibold text-uppercase">Platform MRR</span>
                    <i class="bi bi-graph-up-arrow text-warning fs-4"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">₹ {{ number_format((float) $mrr, 2) }}</h3>
                <small class="text-muted">{{ $activeSubscriptions }} active subscriptions</small>
            </div>
        </div>
    </div>

    <!-- Total Collected Revenue -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-hk h-100 border-start border-4 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-semibold text-uppercase">Total Collected</span>
                    <i class="bi bi-wallet2 text-info fs-4"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">₹ {{ number_format((float) $totalRevenue, 2) }}</h3>
                <small class="text-muted">{{ $successfulPaymentsCount }} successful transactions</small>
            </div>
        </div>
    </div>
</div>

<!-- Middle Section: Recent Shops & Recent Subscription Payments -->
<div class="row g-4 mb-4">
    <!-- Recent Shops -->
    <div class="col-lg-6">
        <div class="card card-hk h-100 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark">
                    <i class="bi bi-buildings text-primary me-2"></i> Recently Registered Shops
                </span>
                <a href="{{ route('admin.shops.index') }}" class="btn btn-link btn-sm text-decoration-none p-0">
                    All Shops &rarr;
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 45%;">Shop Name</th>
                            <th style="width: 30%;">Owner &amp; Mobile</th>
                            <th class="text-end pe-3" style="width: 25%;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentShops as $shop)
                            <tr>
                                <td class="ps-3 py-2">
                                    <a href="{{ route('admin.shops.show', $shop) }}" class="fw-bold text-dark text-decoration-none small d-block">
                                        {{ $shop->name }}
                                    </a>
                                    <small class="text-muted" style="font-size: 0.75rem;">Reg: {{ $shop->created_at->format('d M Y') }}</small>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">{{ $shop->owner_name }}</div>
                                    <small class="text-muted" style="font-size: 0.75rem;">+91 {{ $shop->phone }}</small>
                                </td>
                                <td class="text-end pe-3">
                                    <span class="badge {{ $shop->status === 'active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }} px-2 py-1">
                                        {{ ucfirst($shop->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted small">No registered shops found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Subscription Payments -->
    <div class="col-lg-6">
        <div class="card card-hk h-100 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark">
                    <i class="bi bi-credit-card text-success me-2"></i> Recent Platform Payments
                </span>
                <a href="{{ route('admin.payments.index') }}" class="btn btn-link btn-sm text-decoration-none p-0">
                    All Payments &rarr;
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 40%;">Shop &amp; Plan</th>
                            <th style="width: 30%;">Amount</th>
                            <th class="text-end pe-3" style="width: 30%;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentPayments as $pmt)
                            <tr>
                                <td class="ps-3 py-2">
                                    <div class="fw-bold text-dark small">{{ $pmt->tenant ? $pmt->tenant->name : 'N/A' }}</div>
                                    <small class="text-muted font-monospace" style="font-size: 0.75rem;">{{ $pmt->plan ? $pmt->plan->name : 'SaaS Plan' }}</small>
                                </td>
                                <td>
                                    <strong class="text-dark">₹ {{ number_format((float) $pmt->amount, 2) }}</strong>
                                    <br><small class="text-muted" style="font-size: 0.72rem;">{{ $pmt->created_at ? $pmt->created_at->format('d M, h:i A') : '' }}</small>
                                </td>
                                <td class="text-end pe-3">
                                    @if ($pmt->status === 'successful')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Success</span>
                                    @elseif ($pmt->status === 'pending')
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Pending</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Failed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted small">No payment transactions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Section: Admin Activity Audit Trail -->
<div class="card card-hk shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-shield-lock-fill me-2 text-warning"></i> Recent Admin Audit Activity Logs
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4" style="width: 20%;">Date &amp; Time</th>
                    <th style="width: 20%;">Admin User</th>
                    <th style="width: 20%;">Action</th>
                    <th style="width: 40%;">Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentActivities as $act)
                    <tr>
                        <td class="ps-4 small text-dark fw-semibold">
                            {{ $act->created_at ? $act->created_at->format('d M Y, h:i A') : 'N/A' }}
                        </td>
                        <td class="small">{{ $act->user ? $act->user->name : 'Super Admin' }}</td>
                        <td>
                            <span class="badge bg-light text-dark border font-monospace text-uppercase" style="font-size: 0.72rem;">
                                {{ str_replace('_', ' ', $act->action) }}
                            </span>
                        </td>
                        <td class="small text-muted text-truncate" style="max-width: 400px;" title="{{ $act->description }}">
                            {{ $act->description }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted small">No administrative actions logged yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

