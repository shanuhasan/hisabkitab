@extends('layouts.app')

@section('title', 'Customers (Grahak) — HisabKitab')
@section('header', 'Customers (Grahak Suchi)')
@section('subheader', 'Manage your customer credit accounts, track balances and send reminders')

@section('header-actions')
<a href="{{ route('customers.create') }}" class="btn btn-primary fw-semibold shadow-sm">
    <i class="bi bi-person-plus-fill me-1"></i> Add New Customer
</a>
@endsection

@section('content')
<!-- Metric Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-4">
        <div class="card card-hk border-start border-danger border-4">
            <div class="card-body py-3">
                <div class="text-muted small fw-semibold">Total You'll Get (Udhaar)</div>
                <div class="h4 fw-bold text-danger mb-0">₹ {{ number_format((float) $totalReceivable, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card card-hk border-start border-success border-4">
            <div class="card-body py-3">
                <div class="text-muted small fw-semibold">Total You'll Give (Advance)</div>
                <div class="h4 fw-bold text-success mb-0">₹ {{ number_format(abs((float) $totalAdvance), 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card card-hk border-start border-primary border-4">
            <div class="card-body py-3">
                <div class="text-muted small fw-semibold">Active Customers</div>
                <div class="h4 fw-bold text-slate-800 mb-0">{{ $totalCustomerCount }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter Controls -->
<div class="card card-hk mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('customers.index') }}" class="row g-2 align-items-center">
            <!-- Search Input -->
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by customer name or 10-digit mobile..." value="{{ $search }}">
                    @if (!empty($search))
                        <a href="{{ route('customers.index', ['balance' => $balanceFilter, 'status' => $archiveFilter]) }}" class="btn btn-outline-secondary" title="Clear Search">
                            <i class="bi bi-x"></i>
                        </a>
                    @endif
                </div>
            </div>

            <!-- Balance Filter Tabs -->
            <div class="col-md-4">
                <select name="balance" class="form-select" onchange="this.form.submit()">
                    <option value="all" {{ $balanceFilter === 'all' ? 'selected' : '' }}>All Balances</option>
                    <option value="receivable" {{ $balanceFilter === 'receivable' ? 'selected' : '' }}>You'll Get (Udhaar > 0)</option>
                    <option value="advance" {{ $balanceFilter === 'advance' ? 'selected' : '' }}>You'll Give (Advance < 0)</option>
                    <option value="settled" {{ $balanceFilter === 'settled' ? 'selected' : '' }}>Settled (₹ 0.00)</option>
                </select>
            </div>

            <!-- Sort By -->
            <div class="col-md-3">
                <select name="sort" class="form-select" onchange="this.form.submit()">
                    <option value="latest" {{ $sortBy === 'latest' ? 'selected' : '' }}>Sort: Latest Added</option>
                    <option value="name_asc" {{ $sortBy === 'name_asc' ? 'selected' : '' }}>Sort: Name (A-Z)</option>
                    <option value="balance_desc" {{ $sortBy === 'balance_desc' ? 'selected' : '' }}>Sort: Highest Udhaar</option>
                    <option value="balance_asc" {{ $sortBy === 'balance_asc' ? 'selected' : '' }}>Sort: Lowest Balance</option>
                </select>
            </div>

            <input type="hidden" name="status" value="{{ $archiveFilter }}">
        </form>

        <!-- Status Filter Links -->
        <div class="d-flex align-items-center gap-2 mt-3 pt-2 border-top">
            <span class="text-muted small me-2">View:</span>
            <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'active'])) }}" class="btn btn-sm {{ $archiveFilter === 'active' ? 'btn-primary' : 'btn-light border' }}">
                Active Customers ({{ $totalCustomerCount }})
            </a>
            <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'archived'])) }}" class="btn btn-sm {{ $archiveFilter === 'archived' ? 'btn-danger' : 'btn-light border' }}">
                Archived Customers ({{ $archivedCount }})
            </a>
        </div>
    </div>
</div>

<!-- Customer List Table -->
<div class="card card-hk shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4" style="width: 32%;">Customer Details</th>
                    <th style="width: 25%;">Phone / Contact</th>
                    <th class="text-end" style="width: 25%;">Current Balance</th>
                    <th class="text-end pe-4" style="width: 18%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td class="ps-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.1rem;">
                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('customers.show', $customer) }}" class="fw-bold text-dark text-decoration-none">
                                        {{ $customer->name }}
                                    </a>
                                    @if ($customer->address)
                                        <div class="text-muted small text-truncate" style="max-width: 220px;">
                                            <i class="bi bi-geo-alt me-1"></i>{{ $customer->address }}
                                        </div>
                                    @endif
                                    @if ($customer->credit_limit > 0)
                                        <div class="text-muted" style="font-size: 0.75rem;">Limit: ₹ {{ number_format((float) $customer->credit_limit, 2) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">
                                <i class="bi bi-telephone text-muted me-1"></i> +91 {{ $customer->phone }}
                            </div>
                            @if ($customer->last_transaction_at)
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    Last active: {{ $customer->last_transaction_at->format('d M Y') }}
                                </small>
                            @else
                                <small class="text-muted" style="font-size: 0.75rem;">No transactions yet</small>
                            @endif
                        </td>
                        <td class="text-end">
                            @if ($customer->current_balance > 0)
                                <div class="amount-credit fs-5">₹ {{ number_format((float) $customer->current_balance, 2) }}</div>
                                <span class="badge badge-credit">You'll Get (Udhaar)</span>
                            @elseif ($customer->current_balance < 0)
                                <div class="amount-payment fs-5">₹ {{ number_format(abs((float) $customer->current_balance), 2) }}</div>
                                <span class="badge badge-payment">You'll Give (Advance)</span>
                            @else
                                <div class="text-muted fw-bold fs-5">₹ 0.00</div>
                                <span class="badge bg-light text-secondary border">Settled</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-primary" title="View Customer Ledger">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                @if (!$customer->trashed())
                                    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-secondary" title="Edit Customer">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('customers.archive', $customer) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to archive {{ $customer->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Archive Customer">
                                            <i class="bi bi-archive"></i>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('customers.restore', $customer->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" title="Restore Customer">
                                            <i class="bi bi-arrow-counterclockwise"></i> Restore
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <div class="text-muted">
                                <div class="mb-3">
                                    <i class="bi bi-people display-4 text-secondary opacity-50"></i>
                                </div>
                                <h5 class="fw-bold text-dark">No Customers Found</h5>
                                @if (!empty($search))
                                    <p class="small mb-3">No customers matched your search query "<strong>{{ $search }}</strong>".</p>
                                    <a href="{{ route('customers.index') }}" class="btn btn-outline-primary btn-sm">Clear Search Filter</a>
                                @elseif ($archiveFilter === 'archived')
                                    <p class="small mb-0">No archived customers in your shop.</p>
                                @else
                                    <p class="small mb-3">You haven't added any customers to your digital khata yet.</p>
                                    <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-person-plus-fill me-1"></i> Add Your First Customer
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($customers->hasPages())
        <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
            <span class="small text-muted">
                Showing {{ $customers->firstItem() }} to {{ $customers->lastItem() }} of {{ $customers->total() }} customers
            </span>
            <div>
                {{ $customers->links() }}
            </div>
        </div>
    @endif
</div>
@endsection

