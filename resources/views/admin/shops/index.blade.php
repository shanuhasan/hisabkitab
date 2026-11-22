@extends('layouts.app')

@section('title', 'Manage Shops — Super Admin')
@section('header', 'Shop & Tenant Directory')
@section('subheader', 'Inspect, monitor, activate, and suspend registered merchant accounts')

@section('header-actions')
<a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
    &larr; Admin Dashboard
</a>
@endsection

@section('content')
<!-- Filter & Search Bar -->
<div class="card card-hk shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="{{ route('admin.shops.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by shop name, owner, phone, or email..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses (Active &amp; Suspended)</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Suspended Only</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
                @if($search || $status !== 'all')
                    <a href="{{ route('admin.shops.index') }}" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Shops Table -->
<div class="card card-hk shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-shop me-2 text-primary"></i> Registered Merchants ({{ $shops->total() }} Total)
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4" style="width: 25%;">Shop Name &amp; ID</th>
                    <th style="width: 20%;">Owner Name &amp; Phone</th>
                    <th style="width: 15%;">Registered</th>
                    <th style="width: 15%;">Plan Status</th>
                    <th style="width: 10%;">Shop Status</th>
                    <th class="text-center pe-4" style="width: 15%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shops as $shop)
                    <tr>
                        <td class="ps-4">
                            <strong class="text-dark d-block">{{ $shop->name }}</strong>
                            <small class="text-muted font-monospace" style="font-size: 0.75rem;">ID: #{{ $shop->id }} &bull; {{ $shop->slug }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark small">{{ $shop->owner_name }}</div>
                            <small class="text-muted" style="font-size: 0.75rem;">+91 {{ $shop->phone }}</small>
                            @if($shop->email)
                                <br><small class="text-muted" style="font-size: 0.72rem;">{{ $shop->email }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="small text-dark">{{ $shop->created_at->format('d M Y') }}</span>
                            <br><small class="text-muted" style="font-size: 0.72rem;">{{ $shop->created_at->diffForHumans() }}</small>
                        </td>
                        <td>
                            @php $currSub = $shop->currentSubscription; @endphp
                            @if ($currSub && $currSub->plan)
                                <span class="badge bg-light text-dark border">
                                    {{ $currSub->plan->name }}
                                </span>
                                <br>
                                <span class="badge {{ $currSub->statusBadgeClass() }}" style="font-size: 0.68rem;">
                                    {{ ucfirst($currSub->status) }}
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">No Subscription</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $shop->status === 'active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }} px-2 py-1">
                                {{ ucfirst($shop->status) }}
                            </span>
                        </td>
                        <td class="text-center pe-4">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('admin.shops.show', $shop) }}" class="btn btn-outline-primary btn-sm" title="View Full Shop Profile">
                                    <i class="bi bi-eye-fill"></i> View
                                </a>
                                <form action="{{ route('admin.shops.toggle_status', $shop) }}" method="POST" onsubmit="return confirm('Are you sure you want to {{ $shop->status === 'active' ? 'suspend' : 'activate' }} {{ $shop->name }}?');">
                                    @csrf
                                    <button type="submit" class="btn {{ $shop->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }} btn-sm" title="{{ $shop->status === 'active' ? 'Suspend Shop' : 'Activate Shop' }}">
                                        {{ $shop->status === 'active' ? 'Suspend' : 'Activate' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted small">
                            <i class="bi bi-shop-window fs-2 d-block mb-2 opacity-50"></i>
                            No merchant shops found matching your search.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($shops->hasPages())
        <div class="card-footer bg-white border-top py-3">
            {{ $shops->links() }}
        </div>
    @endif
</div>
@endsection

