@extends('layouts.app')

@section('title', 'Plan Management — Super Admin')
@section('header', 'SaaS Subscription Plans')
@section('subheader', 'Configure SaaS subscription packages, pricing, and feature limits')

@section('content')
<div class="card card-hk shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-tags-fill me-2 text-primary"></i> Available SaaS Subscription Tiers
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4" style="width: 20%;">Plan Name &amp; Slug</th>
                    <th style="width: 15%;">Price (₹)</th>
                    <th style="width: 15%;">Duration</th>
                    <th style="width: 15%;">Customer Limit</th>
                    <th style="width: 15%;">Active Shops</th>
                    <th class="text-center pe-4" style="width: 20%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plans as $plan)
                    <tr>
                        <td class="ps-4">
                            <strong class="text-dark">{{ $plan->name }}</strong>
                            <br><small class="text-muted font-monospace">{{ $plan->slug }}</small>
                        </td>
                        <td>
                            <strong class="fs-6 text-dark">₹ {{ number_format((float) $plan->price, 2) }}</strong>
                        </td>
                        <td>{{ $plan->duration_days }} Days</td>
                        <td>
                            <span class="badge {{ $plan->customer_limit === -1 ? 'bg-success' : 'bg-primary' }}">
                                {{ $plan->customer_limit === -1 ? 'Unlimited' : $plan->customer_limit . ' Customers' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ $plan->subscriptions_count }} Subscriptions
                            </span>
                        </td>
                        <td class="text-center pe-4">
                            <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-outline-primary btn-sm fw-semibold">
                                <i class="bi bi-pencil-square me-1"></i> Edit Plan &amp; Limits
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

