# Changelog

All notable changes to the HisabKitab SaaS project will be documented in this file.

## [Phase 1] - 2026-11-22
### Added
- Created clean Laravel 12.73.0 project on PHP 8.2.12.
- Configured MySQL 8.2 database (`hisabkitab`) and environment variables in `.env`.
- Enabled PHP `intl` extension for currency and locale formatting.
- Installed Bootstrap 5, Bootstrap Icons, Chart.js via npm and configured Vite.
- Installed `barryvdh/laravel-dompdf` for PDF generation.
- Created multi-tenant database migrations:
  - `tenants`, `users`, `customers`, `transactions`, `transaction_reversals`, `plans`, `subscriptions`, `whatsapp_logs`.
- Implemented `TenantScope` and `BelongsToTenant` trait for strict server-side tenant isolation.
- Created core models: `Tenant`, `User`, `Customer`, `Transaction`, `TransactionReversal`, `Plan`, `Subscription`, `WhatsappLog`.
- Created authorization policies: `CustomerPolicy`, `TransactionPolicy`.
- Created core business service skeletons: `TransactionService`, `BalanceService`, `ReportService`, `WhatsappService`.
- Built reusable UI layouts and components:
  - `resources/views/layouts/app.blade.php` (Authenticated layout with mobile sidebar)
  - `resources/views/layouts/guest.blade.php` (Guest layout)
  - `resources/views/layouts/partials/header.blade.php`
  - `resources/views/layouts/partials/sidebar.blade.php`
  - `resources/views/layouts/partials/footer.blade.php`
  - `resources/views/layouts/partials/alerts.blade.php`
  - `resources/views/welcome.blade.php` (Landing page)
- Implemented and passed automated test suite (`Phase1ArchitectureTest`).
