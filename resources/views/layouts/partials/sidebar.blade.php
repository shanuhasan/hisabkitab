<!-- Sidebar Component -->
<aside class="hk-sidebar" id="hkSidebar">
    <!-- Brand / Logo Area -->
    <div class="hk-sidebar-brand">
        <i class="bi bi-journal-bookmark-fill"></i>
        <span>Hisab<span class="text-info">Kitab</span></span>
    </div>

    <!-- Shop Identity Badge -->
    <div class="px-3 py-2 bg-dark bg-opacity-50 border-bottom border-secondary border-opacity-25">
        <div class="d-flex align-items-center">
            <div class="rounded-circle bg-primary bg-opacity-25 text-primary p-2 me-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="bi bi-shop fs-5"></i>
            </div>
            <div class="text-truncate">
                <div class="fw-semibold text-white small text-truncate">
                    {{ auth()->user()->tenant->name ?? 'Demo Kirana Store' }}
                </div>
                <div class="text-muted" style="font-size: 0.75rem;">
                    {{ auth()->user()->role ?? 'Shop Owner' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Menu Items -->
    <nav class="hk-sidebar-menu">
        <div class="hk-sidebar-heading">Main Overview</div>
        <ul class="nav flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('customers*') ? 'active' : '' }}" href="{{ route('customers.index') }}">
                    <i class="bi bi-people-fill"></i>
                    <span>Customers (Grahak)</span>
                </a>
            </li>
        </ul>

        <div class="hk-sidebar-heading">Transactions (Len-Den)</div>
        <ul class="nav flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('transactions.credit*') ? 'active' : '' }}" href="{{ route('transactions.credit') }}">
                    <i class="bi bi-arrow-up-right-circle-fill text-danger"></i>
                    <span>Udhaar Dena (Gave)</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('transactions.payment*') ? 'active' : '' }}" href="{{ route('transactions.payment') }}">
                    <i class="bi bi-arrow-down-left-circle-fill text-success"></i>
                    <span>Jama Lena (Got)</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('transactions.history*') ? 'active' : '' }}" href="{{ route('transactions.history') }}">
                    <i class="bi bi-receipt"></i>
                    <span>Ledger History</span>
                </a>
            </li>
        </ul>

        <div class="hk-sidebar-heading">Reports & Communication</div>
        <ul class="nav flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link {{ request()->is('reports*') ? 'active' : '' }}" href="{{ url('/reports') }}">
                    <i class="bi bi-bar-chart-line-fill"></i>
                    <span>Reports & Aging</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('reminders*') ? 'active' : '' }}" href="{{ url('/reminders') }}">
                    <i class="bi bi-whatsapp text-success"></i>
                    <span>WhatsApp Reminders</span>
                </a>
            </li>
        </ul>

        <div class="hk-sidebar-heading">Settings & SaaS</div>
        <ul class="nav flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('shop.profile*') ? 'active' : '' }}" href="{{ route('shop.profile') }}">
                    <i class="bi bi-gear-fill"></i>
                    <span>Shop Settings</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('subscription*') ? 'active' : '' }}" href="{{ url('/subscription') }}">
                    <i class="bi bi-credit-card-2-front-fill"></i>
                    <span>Subscription Plan</span>
                </a>
            </li>
            @if(auth()->user() && auth()->user()->role === 'super_admin')
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin*') ? 'active' : '' }}" href="{{ url('/admin') }}">
                        <i class="bi bi-shield-lock-fill text-warning"></i>
                        <span>Super Admin Panel</span>
                    </a>
                </li>
            @endif
        </ul>
    </nav>

    <!-- Sidebar Footer -->
    <div class="hk-sidebar-footer">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center text-white small">
                <i class="bi bi-shield-check text-success me-2"></i>
                <span>Tenant Isolated</span>
            </div>
            <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25" style="font-size: 0.7rem;">v1.0</span>
        </div>
    </div>
</aside>

