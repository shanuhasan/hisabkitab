@extends('layouts.app')

@section('title', 'Dashboard — ' . ($tenant->name ?? 'HisabKitab'))
@section('header', ($tenant->name ?? 'Shop') . ' Dashboard')
@section('subheader', 'Digital Udhaar & Jama Khata Overview')

@section('header-actions')
<div class="d-flex gap-2">
    <a href="{{ url('/customers') }}" class="btn btn-outline-primary btn-sm fw-semibold">
        <i class="bi bi-people me-1"></i> View Customers
    </a>
</div>
@endsection

@section('content')
<!-- KPI Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Total Udhaar to Receive -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-hk h-100 border-start border-danger border-4">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Total Udhaar (To Receive)</span>
                    <div class="rounded-circle bg-danger-subtle text-danger p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-arrow-up-right-circle fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-danger mb-1">₹ {{ $metrics['total_udhaar_receivable'] }}</h3>
                <span class="text-muted small">You will get from customers</span>
            </div>
        </div>
    </div>

    <!-- Total Advance Received -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-hk h-100 border-start border-success border-4">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Total Advance (To Give)</span>
                    <div class="rounded-circle bg-success-subtle text-success p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-arrow-down-left-circle fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-success mb-1">₹ {{ $metrics['total_advance_payable'] }}</h3>
                <span class="text-muted small">Advance payments from customers</span>
            </div>
        </div>
    </div>

    <!-- Total Active Customers -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-hk h-100 border-start border-primary border-4">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Total Customers (Grahak)</span>
                    <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-people-fill fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-slate-800 mb-1">{{ $metrics['total_customers'] }}</h3>
                <span class="text-muted small">Registered in this shop</span>
            </div>
        </div>
    </div>

    <!-- Today's Collection -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-hk h-100 border-start border-info border-4">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Today's Jama (Collection)</span>
                    <div class="rounded-circle bg-info-subtle text-info p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-cash-stack fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-slate-800 mb-1">₹ {{ $metrics['today_payment'] }}</h3>
                <span class="text-muted small">Received today</span>
            </div>
        </div>
    </div>
</div>

<!-- Quick Action Shortcuts -->
<div class="card card-hk mb-4">
    <div class="card-header fw-bold">
        <i class="bi bi-lightning-charge-fill text-warning me-2"></i> Quick Actions
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <a href="{{ url('/transactions/credit') }}" class="btn btn-outline-danger w-100 py-3 d-flex align-items-center justify-content-center gap-2 fw-semibold">
                    <i class="bi bi-arrow-up-right-circle fs-4"></i>
                    <span>+ Udhaar Dena (Gave Credit)</span>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ url('/transactions/payment') }}" class="btn btn-outline-success w-100 py-3 d-flex align-items-center justify-content-center gap-2 fw-semibold">
                    <i class="bi bi-arrow-down-left-circle fs-4"></i>
                    <span>+ Jama Lena (Got Payment)</span>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ url('/customers/create') }}" class="btn btn-outline-primary w-100 py-3 d-flex align-items-center justify-content-center gap-2 fw-semibold">
                    <i class="bi bi-person-plus fs-4"></i>
                    <span>Add New Customer (Grahak)</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Shop Status & Info Card -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card card-hk h-100">
            <div class="card-header fw-bold">
                <i class="bi bi-info-circle text-primary me-2"></i> Shop Profile Overview
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted" style="width: 140px;">Shop Name:</td>
                            <td class="fw-semibold">{{ $tenant->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Owner Name:</td>
                            <td class="fw-semibold">{{ $tenant->owner_name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Mobile Number:</td>
                            <td>+91 {{ $tenant->phone }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">GSTIN:</td>
                            <td>{{ $tenant->gstin ?? 'Not Provided' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tenant Isolation:</td>
                            <td><span class="badge bg-success-subtle text-success border border-success-subtle">Active (ID: #{{ $tenant->id }})</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-top text-end">
                <a href="{{ route('shop.profile') }}" class="btn btn-link btn-sm text-decoration-none">Edit Profile Settings &rarr;</a>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card card-hk h-100">
            <div class="card-header fw-bold">
                <i class="bi bi-patch-check text-success me-2"></i> Subscription Status
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="fw-bold mb-0">Free Starter Trial</h5>
                        <small class="text-muted">Includes WhatsApp reminders and PDF statements</small>
                    </div>
                    <span class="badge bg-primary px-3 py-2">30 Days Active</span>
                </div>
                <div class="progress mb-3" style="height: 8px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 10%;" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <p class="small text-muted mb-0">
                    Customer limit: <strong>100 customers</strong>. Upgrade your subscription anytime for unlimited customers.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

