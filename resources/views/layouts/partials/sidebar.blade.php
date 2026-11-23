<!-- Sidebar Component -->
<aside class="hk-sidebar" id="hkSidebar">
    <!-- Brand / Logo Area -->
    <div class="hk-sidebar-brand">
        <i class="bi bi-journal-bookmark-fill"></i>
        <span>Udhaar<span class="text-info">Hisab</span></span>
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
        </ul>

        @if(auth()->user() && auth()->user()->role === 'super_admin')
            <div class="hk-sidebar-heading text-warning">Super Admin Panel</div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-speedometer text-warning"></i>
                        <span>Admin Overview</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.shops*') ? 'active' : '' }}" href="{{ route('admin.shops.index') }}">
                        <i class="bi bi-shop text-info"></i>
                        <span>Manage Shops</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.plans*') ? 'active' : '' }}" href="{{ route('admin.plans.index') }}">
                        <i class="bi bi-tags-fill text-primary"></i>
                        <span>SaaS Plans</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.payments*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">
                        <i class="bi bi-cash-stack text-success"></i>
                        <span>Gateway Payments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                        <i class="bi bi-gear-fill text-secondary"></i>
                        <span>Platform Settings</span>
                    </a>
                </li>
            </ul>
        @endif
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

