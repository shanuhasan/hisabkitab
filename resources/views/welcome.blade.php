@extends('layouts.guest')

@section('title', 'HisabKitab — Digital Udhaar Management SaaS for Indian Shopkeepers')

@section('content')
<div class="row align-items-center py-5">
    <div class="col-lg-7 text-center text-lg-start mb-5 mb-lg-0">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold mb-3">
            🇮🇳 Made for Indian Small Businesses & Kirana Stores
        </span>
        <h1 class="display-4 fw-bold text-slate-900 mb-3 lh-sm">
            Digital Udhaar & Jama Khata <br>
            <span class="text-primary">100% Secure & Simple</span>
        </h1>
        <p class="lead text-muted mb-4">
            Replace paper bahi-khata notebooks with HisabKitab. Track customer credit (Udhaar Dena), record payments (Jama Lena), send instant WhatsApp reminders, and generate audited PDF statements.
        </p>
        <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
            <a href="{{ Route::has('register') ? route('register') : url('/register') }}" class="btn btn-primary btn-lg px-4 shadow-sm fw-semibold">
                <i class="bi bi-rocket-takeoff-fill me-2"></i> Open Digital Khata (Free Trial)
            </a>
            <a href="{{ Route::has('login') ? route('login') : url('/login') }}" class="btn btn-outline-secondary btn-lg px-4 fw-semibold">
                <i class="bi bi-box-arrow-in-right me-2"></i> Shopkeeper Login
            </a>
        </div>
        <div class="d-flex align-items-center gap-4 mt-4 pt-2 text-muted small justify-content-center justify-content-lg-start">
            <div><i class="bi bi-shield-check text-success me-1"></i> Multi-Tenant Isolated</div>
            <div><i class="bi bi-check2-circle text-primary me-1"></i> Zero Math Errors</div>
            <div><i class="bi bi-whatsapp text-success me-1"></i> 1-Tap Reminders</div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card card-hk border-0 shadow-lg p-3">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary text-white p-2 me-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="bi bi-shop fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold">Sharma General Store</div>
                            <small class="text-muted">GSTIN: 07AAAAA0000A1Z5</small>
                        </div>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                </div>

                <!-- Sample Ledger Preview -->
                <div class="bg-light p-3 rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Total Udhaar to Collect</span>
                        <span class="text-danger fw-bold fs-5">₹ 42,500.00</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Total Jama Today</span>
                        <span class="text-success fw-bold">₹ 8,200.00</span>
                    </div>
                </div>

                <div class="list-group list-group-flush small">
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                        <div>
                            <div class="fw-semibold">Ramesh Kumar (Plumber)</div>
                            <small class="text-muted">22 Nov 2026 • Cash payment</small>
                        </div>
                        <span class="amount-payment">+ ₹ 1,500.00</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                        <div>
                            <div class="fw-semibold">Pooja Devi (Teacher)</div>
                            <small class="text-muted">22 Nov 2026 • Grocery items</small>
                        </div>
                        <span class="amount-credit">- ₹ 850.00</span>
                    </div>
                </div>

                <div class="mt-3 pt-2 border-top text-center">
                    <a href="{{ Route::has('register') ? route('register') : url('/register') }}" class="btn btn-outline-primary btn-sm w-100 fw-semibold">
                        Get Started in 1 Minute &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="row g-4 py-5 border-top">
    <div class="col-12 text-center mb-3">
        <h2 class="fw-bold">Everything Your Business Needs</h2>
        <p class="text-muted">Designed specifically for Indian retail shops, kirana stores and wholesalers.</p>
    </div>
    <div class="col-md-4">
        <div class="card card-hk h-100 p-3">
            <div class="card-body">
                <div class="rounded bg-danger-subtle text-danger p-3 d-inline-block mb-3">
                    <i class="bi bi-arrow-up-right-circle fs-3"></i>
                </div>
                <h5 class="fw-bold">Udhaar Dena & Jama Lena</h5>
                <p class="text-muted small mb-0">Record credit and payment transactions in seconds with bill image attachment and running balances.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-hk h-100 p-3">
            <div class="card-body">
                <div class="rounded bg-success-subtle text-success p-3 d-inline-block mb-3">
                    <i class="bi bi-whatsapp fs-3"></i>
                </div>
                <h5 class="fw-bold">1-Click WhatsApp Reminders</h5>
                <p class="text-muted small mb-0">Send instant reminder links directly to customer WhatsApp with their balance summary in Hindi or English.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-hk h-100 p-3">
            <div class="card-body">
                <div class="rounded bg-primary-subtle text-primary p-3 d-inline-block mb-3">
                    <i class="bi bi-file-earmark-pdf fs-3"></i>
                </div>
                <h5 class="fw-bold">Audited PDF Statements</h5>
                <p class="text-muted small mb-0">Generate professional, printable ledger statements with shop branding, dates, and clear running balance.</p>
            </div>
        </div>
    </div>
</div>
@endsection
