# Changelog

All notable changes to the HisabKitab SaaS project will be documented in this file.

## [Phase 2] - 2026-11-22
### Added
- Implemented Shop Owner Registration with atomic DB transaction creating Tenant, Owner User, and 30-day Free Trial.
- Implemented Login supporting both Indian mobile numbers (`^[6-9]\d{9}$`) and email addresses.
- Implemented brute-force login rate limiting (5 attempts/minute) using Laravel RateLimiter.
- Implemented Logout with full session invalidation and CSRF token regeneration.
- Implemented Forgot Password and Reset Password recovery flow using `password_reset_tokens`.
- Created `EnsureTenantIsActive` middleware (`tenant.active`) to deny access if a shop is suspended or user is deactivated.
- Implemented IDOR-safe Shop Profile Management (`/shop/profile`) strictly bound to `auth()->user()->tenant`.
- Built Blade views:
  - `resources/views/auth/register.blade.php`
  - `resources/views/auth/login.blade.php`
  - `resources/views/auth/forgot-password.blade.php`
  - `resources/views/auth/reset-password.blade.php`
  - `resources/views/shop/profile.blade.php`
  - `resources/views/dashboard.blade.php`
- Created and passed automated test suite in `tests/Feature/Phase2AuthenticationAndMultiTenancyTest.php` (7 tests, 52 assertions).

## [Phase 1] - 2026-11-22
### Added
- Created clean Laravel 12.73.0 project on PHP 8.2.12.
- Configured MySQL 8.2 database (`hisabkitab`) and environment variables in `.env`.
- Enabled PHP `intl` and `bcmath` extensions.
- Installed Bootstrap 5, Bootstrap Icons, Chart.js via npm; installed `barryvdh/laravel-dompdf` via composer.
- Built multi-tenant database migrations with `DECIMAL(15,2)` precision and foreign keys.
- Implemented `TenantScope` and `BelongsToTenant` trait for strict server-side tenant isolation.
- Created reusable layouts (`app.blade.php`, `guest.blade.php`, `header.blade.php`, `sidebar.blade.php`, `footer.blade.php`, `alerts.blade.php`, `welcome.blade.php`).
