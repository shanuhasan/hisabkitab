@extends('layouts.app')

@section('title', 'Subscription & Plans — UdhaarHisab SaaS')
@section('header', 'Subscription Management')
@section('subheader', 'Manage your SaaS plan, customer limits, and billing history')

@section('content')
<!-- Expiry & Grace Period Alerts -->
@if ($status['is_expired'])
    <div class="alert alert-danger border-2 shadow-sm d-flex align-items-center mb-4">
        <i class="bi bi-exclamation-octagon-fill fs-3 me-3"></i>
        <div>
            <h5 class="alert-heading fw-bold mb-1">Aapka Subscription Samapt Ho Chuka Hai!</h5>
            <p class="mb-0 small">
                Your subscription has expired. Business operations and adding new customers are locked. Please choose a plan below to immediately reactivate your digital khata.
            </p>
        </div>
    </div>
@elseif ($status['is_in_grace'])
    <div class="alert alert-warning border-2 shadow-sm d-flex align-items-center mb-4">
        <i class="bi bi-hourglass-split fs-3 me-3 text-warning"></i>
        <div>
            <h5 class="alert-heading fw-bold mb-1">Grace Period Chalu Hai (Temporary Access)</h5>
            <p class="mb-0 small">
                Your subscription expired recently. You are in a 3-day grace period. Please renew today to prevent interruption of service.
            </p>
        </div>
    </div>
@elseif ($status['is_near_expiry'])
    <div class="alert alert-warning border shadow-sm d-flex align-items-center mb-4">
        <i class="bi bi-bell-fill fs-4 me-3 text-warning"></i>
        <div>
            <strong>Renewal Alert:</strong> Your {{ $status['plan']->name }} will expire in <strong>{{ $status['days_remaining'] }} days</strong>. Renew now to maintain uninterrupted access.
        </div>
    </div>
@endif

<!-- Current Plan Overview Card -->
<div class="card card-hk shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-card-checklist text-primary me-2"></i> Current Subscription Status
        </h6>
        <span class="badge {{ $status['subscription']->statusBadgeClass() }} px-3 py-2 text-uppercase fs-6">
            {{ ucfirst(str_replace('_', ' ', $status['subscription']->status)) }}
        </span>
    </div>
    <div class="card-body p-4">
        <div class="row g-4 align-items-center">
            <div class="col-md-4 border-end">
                <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Active Plan</span>
                <h3 class="fw-bold text-primary mb-1">{{ $status['plan']->name }}</h3>
                <div class="text-dark fs-5 fw-semibold">
                    ₹ {{ number_format((float) $status['plan']->price, 2) }} 
                    <small class="text-muted fs-6">/ {{ $status['plan']->duration_days }} days</small>
                </div>
            </div>

            <div class="col-md-4 border-end">
                <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Billing Period &amp; Validity</span>
                <div class="small mb-1">
                    <strong>Started:</strong> {{ $status['subscription']->starts_at ? $status['subscription']->starts_at->format('d M Y') : 'N/A' }}
                </div>
                <div class="small mb-2">
                    <strong>Expires:</strong> {{ $status['subscription']->ends_at ? $status['subscription']->ends_at->format('d M Y') : 'Never' }}
                </div>
                <div>
                    <span class="badge {{ $status['days_remaining'] <= 5 ? 'bg-danger' : 'bg-primary-subtle text-primary border border-primary-subtle' }} px-2 py-1">
                        <i class="bi bi-clock-history me-1"></i> {{ $status['days_remaining'] }} Days Remaining
                    </span>
                </div>
            </div>

            <div class="col-md-4">
                <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Customer Quota Usage</span>
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-dark fs-5">{{ $status['customer_count'] }}</span>
                    <span class="text-muted small">
                        of {{ $status['customer_limit'] === -1 ? 'Unlimited' : $status['customer_limit'] }} allowed
                    </span>
                </div>
                @if ($status['customer_limit'] > 0)
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar {{ $status['usage_percent'] >= 90 ? 'bg-danger' : ($status['usage_percent'] >= 75 ? 'bg-warning' : 'bg-success') }}" 
                             role="progressbar" 
                             style="width: {{ $status['usage_percent'] }}%" 
                             aria-valuenow="{{ $status['usage_percent'] }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                    <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">
                        {{ $status['usage_percent'] }}% customer capacity used
                    </small>
                @else
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%;"></div>
                    </div>
                    <small class="text-success mt-1 d-block fw-semibold" style="font-size: 0.75rem;">
                        <i class="bi bi-infinity"></i> Unlimited customer capacity
                    </small>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Plan Comparison / Pricing Tiers -->
<div class="row g-4 mb-5">
    @foreach ($plans as $plan)
        @php
            $isCurrent = $status['plan']->id === $plan->id;
            $isPro = $plan->slug === 'pro';
        @endphp
        <div class="col-lg-4">
            <div class="card card-hk h-100 shadow-sm position-relative {{ $isPro ? 'border-primary border-2' : '' }} {{ $isCurrent ? 'bg-light bg-opacity-50' : '' }}">
                @if ($isPro)
                    <span class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-primary px-3 py-1 text-uppercase fw-bold shadow-sm">
                        Most Popular / सबसे लोकप्रिय
                    </span>
                @endif
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h4 class="fw-bold mb-0 {{ $isPro ? 'text-primary' : 'text-dark' }}">{{ $plan->name }}</h4>
                        @if ($isCurrent)
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active Plan</span>
                        @endif
                    </div>
                    <p class="text-muted small mb-3">{{ $plan->description }}</p>

                    <div class="mb-4">
                        <span class="display-6 fw-bold text-dark">₹ {{ (int)$plan->price }}</span>
                        <span class="text-muted">/ {{ $plan->duration_days === 365 ? 'year' : 'month' }}</span>
                    </div>

                    <ul class="list-unstyled mb-4 flex-grow-1 small" style="line-height: 2;">
                        <li>
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <strong>{{ $plan->customer_limit === -1 ? 'Unlimited' : $plan->customer_limit }}</strong> Customers Limit
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Basic Udhaar &amp; Jama Ledger
                        </li>
                        <li>
                            <i class="bi {{ $plan->hasFeature('whatsapp_reminders') ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }} me-2"></i>
                            WhatsApp Click-to-Chat Reminders
                        </li>
                        <li>
                            <i class="bi {{ $plan->hasFeature('pdf_statements') ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }} me-2"></i>
                            Downloadable PDF Statements &amp; Receipts
                        </li>
                        <li>
                            <i class="bi {{ $plan->hasFeature('csv_exports') ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }} me-2"></i>
                            Excel / CSV Ledger Exports
                        </li>
                        <li>
                            <i class="bi {{ $plan->hasFeature('advanced_reports') ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }} me-2"></i>
                            Advanced Business Analytics &amp; Aging Reports
                        </li>
                    </ul>

                    <div class="mt-auto">
                        @if ($isCurrent)
                            <button class="btn btn-outline-secondary w-100 fw-semibold disabled" disabled>
                                <i class="bi bi-check2 me-1"></i> Current Plan
                            </button>
                        @elseif ($plan->isFree())
                            <form action="{{ route('subscription.downgrade.free') }}" method="POST" onsubmit="return confirm('Kya aap nishchit roop se Free Plan par switch karna chahte hain?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary w-100 fw-semibold">
                                    Switch to Free
                                </button>
                            </form>
                        @else
                            <button type="button" 
                                    class="btn {{ $isPro ? 'btn-primary' : 'btn-outline-primary' }} w-100 fw-bold shadow-sm"
                                    onclick="initiateCheckout({{ $plan->id }}, '{{ $plan->name }}', {{ $plan->price }})">
                                <i class="bi bi-lightning-charge-fill me-1"></i> 
                                {{ $status['plan']->price < $plan->price ? 'Upgrade to ' . $plan->name : 'Switch to ' . $plan->name }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Payment History Table -->
<div class="card card-hk shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-receipt me-2 text-primary"></i> Subscription Billing History
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4" style="width: 20%;">Date</th>
                    <th style="width: 20%;">Order / Reference</th>
                    <th style="width: 20%;">Plan</th>
                    <th style="width: 20%;">Amount Paid</th>
                    <th class="text-center pe-4" style="width: 20%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $pmt)
                    <tr>
                        <td class="ps-4 small text-dark fw-semibold">
                            {{ $pmt->created_at ? $pmt->created_at->format('d M Y, h:i A') : 'N/A' }}
                        </td>
                        <td>
                            <code class="small">{{ $pmt->gateway_order_id ?? ('PMT-' . $pmt->id) }}</code>
                            @if ($pmt->gateway_payment_id)
                                <br><small class="text-muted font-monospace">{{ $pmt->gateway_payment_id }}</small>
                            @endif
                        </td>
                        <td class="fw-semibold text-dark">{{ $pmt->plan->name ?? 'SaaS Plan' }}</td>
                        <td class="fw-bold text-dark">₹ {{ number_format((float) $pmt->amount, 2) }}</td>
                        <td class="text-center pe-4">
                            @if ($pmt->status === 'successful')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="bi bi-check-circle-fill me-1"></i> Successful
                                </span>
                            @elseif ($pmt->status === 'pending')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                    <i class="bi bi-hourglass-split me-1"></i> Pending
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" title="{{ $pmt->error_description }}">
                                    <i class="bi bi-x-circle-fill me-1"></i> Failed
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted small">
                            No billing history yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Razorpay Checkout Integration & Modal Simulation -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
function initiateCheckout(planId, planName, planPrice) {
    if (!confirm('Proceed to checkout for ' + planName + ' (₹ ' + planPrice + ')?')) {
        return;
    }

    // Step 1: Request Order ID from Backend
    fetch('{{ route("subscription.checkout.order") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ plan_id: planId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status !== 'success') {
            alert(data.message || 'Error generating order.');
            return;
        }

        const order = data.order;

        // If Razorpay SDK is available and non-dummy key
        if (typeof Razorpay !== 'undefined' && !order.key_id.includes('test_dummy')) {
            const options = {
                key: order.key_id,
                amount: order.amount,
                currency: order.currency,
                name: 'UdhaarHisab SaaS',
                description: 'Subscription for ' + order.plan_name,
                order_id: order.order_id,
                prefill: {
                    name: '{{ auth()->user()->name }}',
                    contact: '{{ auth()->user()->phone }}',
                    email: '{{ auth()->user()->email }}'
                },
                theme: { color: '#0d6efd' },
                handler: function (response) {
                    // Step 2: Verify signature on backend
                    verifyPaymentOnServer(response.razorpay_order_id, response.razorpay_payment_id, response.razorpay_signature, planId);
                }
            };
            const rzp = new Razorpay(options);
            rzp.open();
        } else {
            // Local Test Environment Simulator:
            // Simulates verified callback signature directly with backend
            alert('Opening Razorpay Test Checkout for order: ' + order.order_id);
            const mockPaymentId = 'pay_' + Math.random().toString(36).substring(2, 12);
            // In test environment, ask backend to verify
            verifyPaymentOnServer(order.order_id, mockPaymentId, 'mock_test_signature', planId);
        }
    })
    .catch(err => {
        alert('Network error while connecting to payment gateway.');
    });
}

function verifyPaymentOnServer(orderId, paymentId, signature, planId) {
    fetch('{{ route("subscription.checkout.verify") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            razorpay_order_id: orderId,
            razorpay_payment_id: paymentId,
            razorpay_signature: signature,
            plan_id: planId
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Verification Error: ' + (data.message || 'Signature mismatch.'));
        }
    })
    .catch(err => {
        alert('Payment verification could not be completed.');
    });
}
</script>
@endsection

