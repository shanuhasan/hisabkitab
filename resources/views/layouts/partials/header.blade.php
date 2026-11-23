<!-- Header / Navbar Component -->
<header class="hk-navbar">
    <div class="d-flex align-items-center justify-content-between w-100">
        <!-- Left: Mobile Menu Toggle & Search Bar -->
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-secondary d-lg-none" type="button" id="hkSidebarToggle" aria-label="Toggle navigation">
                <i class="bi bi-list fs-5"></i>
            </button>

            <div class="d-none d-md-flex align-items-center">
                <span class="badge bg-light text-dark border px-3 py-2 fw-medium">
                    <i class="bi bi-calendar-event me-1 text-primary"></i> {{ date('l, d M Y') }}
                </span>
            </div>
        </div>

        <!-- Right: Actions, Language & User Menu -->
        <div class="d-flex align-items-center gap-3">
            <!-- Quick Add Button -->
            <div class="dropdown">
                <button class="btn btn-primary btn-sm dropdown-toggle fw-semibold px-3" type="button" id="quickActionDrop" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-plus-circle me-1"></i> Quick Entry
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="quickActionDrop">
                    <li>
                        <a class="dropdown-item py-2 text-danger fw-semibold" href="{{ route('transactions.credit') }}">
                            <i class="bi bi-arrow-up-right-circle-fill me-2"></i> + Udhaar Dena (Gave Credit)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 text-success fw-semibold" href="{{ route('transactions.payment') }}">
                            <i class="bi bi-arrow-down-left-circle-fill me-2"></i> + Jama Lena (Received Payment)
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('customers.create') }}">
                            <i class="bi bi-person-plus-fill me-2 text-primary"></i> Add New Customer
                        </a>
                    </li>
                </ul>
            </div>

            <!-- User Profile Dropdown -->
            <div class="dropdown">
                <button class="btn btn-light border btn-sm d-flex align-items-center gap-2" type="button" id="userMenuDrop" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.8rem; font-weight: 600;">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <span class="d-none d-sm-inline fw-semibold small text-slate-800">
                        {{ auth()->user()->name ?? 'User' }}
                    </span>
                    <i class="bi bi-chevron-down small text-muted"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="userMenuDrop">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold small">{{ auth()->user()->name ?? 'Shopkeeper' }}</div>
                        <div class="text-muted text-truncate" style="font-size: 0.75rem;">{{ auth()->user()->email ?? 'user@udhaarhisab.test' }}</div>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 small" href="{{ url('/shop/profile') }}">
                            <i class="bi bi-shop me-2 text-muted"></i> Shop Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 small" href="{{ url('/subscription') }}">
                            <i class="bi bi-patch-check me-2 text-muted"></i> Subscription & Invoices
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ Route::has('logout') ? route('logout') : url('/logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item py-2 small text-danger fw-semibold">
                                <i class="bi bi-box-arrow-right me-2"></i> Log Out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
