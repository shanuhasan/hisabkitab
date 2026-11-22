# Changelog

All notable changes to the HisabKitab SaaS project will be documented in this file.

## [Phase 8] - 2026-11-22
### Added
- Created database migration `2026_11_22_000008_create_super_admin_tables.php`:
  - Created `platform_settings` table for site-wide configuration.
  - Created `admin_activity_logs` table for administrative audit trails.
  - Enhanced `subscription_payments` table with `refund_status` and `refunded_at`.
- Created `PlatformSetting` model (`app/Models/PlatformSetting.php`) with key-value accessors (`get`, `set`).
- Created `AdminActivityLog` model (`app/Models/AdminActivityLog.php`) with `log()` helper.
- Created `EnsureUserIsSuperAdmin` middleware (`app/Http/Middleware/EnsureUserIsSuperAdmin.php`) and registered `'super_admin'` alias in `bootstrap/app.php`.
- Created `AdminDashboardController` (`app/Http/Controllers/Admin/AdminDashboardController.php`) computing global SaaS metrics (Total Shops, Active, Suspended, Subscribers, Active Subs, MRR, Revenue Collected) and activity feeds.
- Created `ShopManagementController` (`app/Http/Controllers/Admin/ShopManagementController.php`) with shop search, profile view, and Activate/Suspend toggles.
- Enhanced `PlanController` (`app/Http/Controllers/Admin/PlanController.php`) with `create`, `store`, and safe `destroy` methods protecting plans with active subscribers.
- Created `PaymentManagementController` (`app/Http/Controllers/Admin/PaymentManagementController.php`) with date filtering, status filtering, search, and refund processing.
- Created `PlatformSettingsController` (`app/Http/Controllers/Admin/PlatformSettingsController.php`) managing platform settings.
- Built Super Admin Blade views:
  - `resources/views/admin/dashboard.blade.php`
  - `resources/views/admin/shops/index.blade.php`
  - `resources/views/admin/shops/show.blade.php`
  - `resources/views/admin/plans/create.blade.php`
  - `resources/views/admin/payments/index.blade.php`
  - `resources/views/admin/settings/index.blade.php`
- Updated sidebar (`resources/views/layouts/partials/sidebar.blade.php`) with Super Admin Panel navigation section.
- Registered all `/admin/*` routes in `routes/web.php` protected by `auth` and `super_admin` middleware.
- Implemented and passed automated test suite in `tests/Feature/Phase8SuperAdminPanelTest.php` (8 tests, 41 assertions). Full test suite now passes 63 tests, 334 assertions (0 failures).

## [Phase 7] - 2026-11-22
### Added
- Created official SaaS subscription plans (`PlanSeeder`): Free (₹0, 25 customers), Basic (₹99/mo, 200 customers), Pro (₹199/mo, unlimited customers).
- Added database migration `2026_11_22_000007_create_subscription_management_tables.php`.
- Created `SubscriptionPayment` model and `SubscriptionService` with Razorpay orders, HMAC-SHA256 signature verification, webhooks, and quotas.
- Created `EnforceSubscriptionLimits` middleware and registered alias in `bootstrap/app.php`.
- Created `SubscriptionController` and shop owner subscription portal (`/subscription`).
- Implemented and passed automated test suite in `tests/Feature/Phase7SubscriptionManagementTest.php` (9 tests, 54 assertions).

## [Phase 6] - 2026-11-22
### Added
- Created `WhatsappService` with multi-language reminder templates and click-to-chat URL generator.
- Created `CurrencyHelper` to convert monetary amounts to Indian currency words.
- Created `WhatsappController` and updated `whatsapp_logs` table.
- Built customer account statement PDF generator and payment receipt PDF generator.
- Updated `show.blade.php` with interactive WhatsApp Reminder Modal and communication history table.
- Implemented and passed automated test suite in `tests/Feature/Phase6WhatsAppAndPdfTest.php` (8 tests, 37 assertions).

## [Phase 5] - 2026-11-22
### Added
- Created `ReportService` providing dashboard metrics, period reports, customer summaries, and collection reports.
- Updated `DashboardController` and `ReportController` supporting tabbed views, date filtering, PDF export, and streamed CSV export.
- Redesigned `resources/views/dashboard.blade.php` with 6 metric cards, 2 Chart.js analytics charts, recent transactions, and top debtor accounts.
- Built DomPDF report template `resources/views/reports/pdf.blade.php`.
- Implemented and passed automated test suite in `tests/Feature/Phase5DashboardAndReportingTest.php` (9 tests, 44 assertions).

## [Phase 4] - 2026-11-22
### Added
- Implemented core financial engine (`TransactionService`) using atomic DB transactions and pessimistic row locking (`lockForUpdate()`).
- High-precision monetary arithmetic strictly using `bcmath` (`DECIMAL(15,2)` precision).
- Implemented Udhaar Dena (credit entry) module (`/transactions/credit`) with bill reference and photo attachment.
- Implemented Jama Lena (payment entry) module (`/transactions/payment`) with Cash, UPI, Bank Transfer, Cheque modes and balance validation.
- Added payment validation against customer outstanding balance with advance option and partial payment support.
- Built immutable audit trail with reversal transactions (`type: reversal`) and audit logs in `transaction_reversals`.
- Built Global Shop Len-Den Register (`/transactions/history`).
- Built dedicated printable Customer Account Statement view (`/customers/{customer}/ledger`).

## [Phase 3] - 2026-11-22
### Added
- Created complete Customer Management module (`CustomerController`, `StoreCustomerRequest`, `UpdateCustomerRequest`).
- Implemented opening balance feature with audited ledger entries.
- Enforced per-tenant phone uniqueness on customers (`tenant_id + phone`).
- Added Customer listing with real-time search by name/mobile, balance filters, status toggle, and sorting.

## [Phase 2] - 2026-11-22
### Added
- Implemented Shop Owner Registration with atomic DB transaction creating Tenant, Owner User, and 30-day Free Trial.
- Implemented Login supporting both Indian mobile numbers and email addresses with rate limiting.
- Implemented Logout and Password Reset recovery flows.
- Created `EnsureTenantIsActive` middleware.
- Implemented IDOR-safe Shop Profile Management.

## [Phase 1] - 2026-11-22
### Added
- Created clean Laravel 12.73.0 project on PHP 8.2.12.
- Configured MySQL 8.2 database (`hisabkitab`) and environment variables.
- Built multi-tenant database migrations with `DECIMAL(15,2)` precision and foreign keys.
- Implemented `TenantScope` and `BelongsToTenant` trait for strict server-side tenant isolation.
- Created reusable layouts (`app.blade.php`, `guest.blade.php`, `header.blade.php`, `sidebar.blade.php`, `footer.blade.php`, `alerts.blade.php`, `welcome.blade.php`).
