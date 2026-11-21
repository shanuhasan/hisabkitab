# Changelog

All notable changes to the HisabKitab SaaS project will be documented in this file.

## [Phase 3] - 2026-11-22
### Added
- Created complete Customer Management module (`CustomerController`, `StoreCustomerRequest`, `UpdateCustomerRequest`).
- Implemented opening balance feature: creates an audited ledger entry in `transactions` with type `credit` (purana udhaar) or `payment` (advance) and initializes `current_balance`.
- Added customer phone number validation enforcing 10-digit Indian mobile format (`^[6-9]\d{9}$`).
- Enforced per-tenant phone uniqueness on customers (`tenant_id + phone`), preventing duplicates in the same shop while allowing different shops to have customers with identical numbers.
- Added Customer listing with real-time search by name/mobile, balance filters (You'll Get, You'll Give, Settled), status toggle (Active / Archived), and sorting.
- Added Customer details page (`/customers/{customer}`) with metrics (Current Balance, All-time Udhaar, All-time Jama), WhatsApp 1-tap reminder button, and paginated transaction history table.
- Added Customer Edit form (`/customers/{customer}/edit`).
- Implemented Archive and Restore functionality using `SoftDeletes` (`deleted_at`) and `status = 'inactive'`.
- Built Blade views:
  - `resources/views/customers/index.blade.php`
  - `resources/views/customers/create.blade.php`
  - `resources/views/customers/edit.blade.php`
  - `resources/views/customers/show.blade.php`
- Implemented and passed automated test suite in `tests/Feature/Phase3CustomerManagementTest.php` (8 tests, 51 assertions).

## [Phase 2] - 2026-11-22
### Added
- Implemented Shop Owner Registration with atomic DB transaction creating Tenant, Owner User, and 30-day Free Trial.
- Implemented Login supporting both Indian mobile numbers (`^[6-9]\d{9}$`) and email addresses.
- Implemented brute-force login rate limiting (5 attempts/minute) using Laravel RateLimiter.
- Implemented Logout with full session invalidation and CSRF token regeneration.
- Implemented Forgot Password and Reset Password recovery flow using `password_reset_tokens`.
- Created `EnsureTenantIsActive` middleware (`tenant.active`) to deny access if a shop is suspended or user is deactivated.
- Implemented IDOR-safe Shop Profile Management (`/shop/profile`) strictly bound to `auth()->user()->tenant`.
- Built Blade views for authentication and dashboard.

## [Phase 1] - 2026-11-22
### Added
- Created clean Laravel 12.73.0 project on PHP 8.2.12.
- Configured MySQL 8.2 database (`hisabkitab`) and environment variables in `.env`.
- Enabled PHP `intl` and `bcmath` extensions.
- Installed Bootstrap 5, Bootstrap Icons, Chart.js via npm; installed `barryvdh/laravel-dompdf` via composer.
- Built multi-tenant database migrations with `DECIMAL(15,2)` precision and foreign keys.
- Implemented `TenantScope` and `BelongsToTenant` trait for strict server-side tenant isolation.
- Created reusable layouts (`app.blade.php`, `guest.blade.php`, `header.blade.php`, `sidebar.blade.php`, `footer.blade.php`, `alerts.blade.php`, `welcome.blade.php`).
