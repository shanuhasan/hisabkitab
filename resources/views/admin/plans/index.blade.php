@extends('layouts.app')

@section('title', 'Plan Management — Super Admin')
@section('header', 'SaaS Subscription Plans')
@section('subheader', 'Configure SaaS subscription packages, pricing, customer limits, and feature permissions')

@section('header-actions')
<div class="d-flex gap-2">
    <a href="{{ route('admin.plans.create') }}" class="btn btn-primary btn-sm fw-semibold shadow-sm">
        <i class="bi bi-plus-circle me-1"></i> Create New Plan
    </a>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        &larr; Admin Dashboard
    </a>
</div>
@endsection

@section('content')
<div class="card card-hk shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-tags-fill me-2 text-primary"></i> Available SaaS Subscription Tiers ({{ $plans->count() }} Total)
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4" style="width: 22%;">Plan Name &amp; Slug</th>
                    <th style="width: 14%;">Price (₹)</th>
                    <th style="width: 14%;">Duration</th>
                    <th style="width: 16%;">Customer Limit</th>
                    <th style="width: 14%;">Active Shops</th>
                    <th class="text-center pe-4" style="width: 20%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plans as $plan)
                    <tr>
                        <td class="ps-4">
                            <strong class="text-dark">{{ $plan->name }}</strong>
                            <br><small class="text-muted font-monospace">{{ $plan->slug }}</small>
                            @if (!$plan->is_active)
                                <span class="badge bg-danger ms-1" style="font-size: 0.65rem;">Disabled</span>
                            @endif
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
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-outline-primary btn-sm fw-semibold" title="Edit Plan">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                                @if ($plan->subscriptions_count === 0)
                                    <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST" onsubmit="return confirm('Delete plan {{ $plan->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete Plan">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
