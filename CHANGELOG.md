# Changelog

All notable changes to the HisabKitab SaaS project will be documented in this file.

## [Phase 10] - 2026-11-22
### Added
- Created production environment template `.env.production.example` with hardened security settings (`APP_ENV=production`, `APP_DEBUG=false`, `FORCE_HTTPS=true`, secure cookie flags, daily error logging).
- Updated `app/Providers/AppServiceProvider.php` to enforce HTTPS when in production or when `FORCE_HTTPS=true`, and set `Paginator::useBootstrapFive()`.
- Added Razorpay service mapping to `config/services.php` to support Laravel `config:cache` without breaking `SubscriptionService`.
- Created artisan command `hisabkitab:backup` (`app/Console/Commands/DatabaseBackupCommand.php`) with timestamped database dumps and 7-day retention pruning.
- Created artisan command `hisabkitab:restore` (`app/Console/Commands/DatabaseRestoreCommand.php`) for interactive or forced database restoration.
- Created artisan command `hisabkitab:check-subscriptions` (`app/Console/Commands/CheckSubscriptionsCommand.php`) for scanning and marking expired tenant subscriptions.
- Registered automated daily tasks in `routes/console.php` (subscription expiry check, database backup at 02:00 AM, and password reset token cleanup).
- Created comprehensive operational and production documentation:
  - `DEPLOYMENT.md` (Complete Nginx, PHP-FPM, Supervisor, SSL, and Cron deployment manual).
  - `docs/BACKUP_GUIDE.md` (Automated, manual, and S3 offsite database backup procedures).
  - `docs/RESTORATION_GUIDE.md` (Disaster recovery and database restoration procedures).
  - `docs/TROUBLESHOOTING.md` (Production issue diagnostic guide for 500 errors, permissions, webhooks, and queues).

## [Phase 9] - 2026-11-22
### Added
- Created database migration `2026_11_22_000009_add_reporting_indexes_to_transactions_table.php`:
  - Added composite index `idx_txns_tenant_rev_type_date` on `['tenant_id', 'is_reversed', 'type', 'transaction_date']`.
  - Added composite index `idx_txns_tenant_rev_date` on `['tenant_id', 'is_reversed', 'transaction_date']`.
- Added rate limiting to `ForgotPasswordController`:
  - Throttled password reset link generation to 5 requests per minute per IP.
  - Throttled password reset submission attempts to 10 requests per minute per IP.
- Added duplicate transaction debounce protection in `TransactionService@recordTransaction`:
  - Rejects rapid duplicate identical transactions within 3 seconds for the same customer, type, and amount.
- Added rate limiting to `SubscriptionController`:
  - Throttled order creation (`createOrder`) to 10 requests per minute per merchant.
  - Throttled payment verification (`verifyPayment`) to 10 requests per minute per merchant.
- Added rate limiting to `WhatsappController`:
  - Throttled reminder initiation (`sendReminder`) to 30 requests per minute per merchant.
- Created `tests/Feature/Phase9SecurityAuditAndQATest.php` (11 tests, 70 assertions) covering rate limiting, duplicate protection, multi-tenant IDOR, Super Admin boundary protection, role spoofing prevention, XSS escaping, SQL injection resistance, payment webhook security, and idempotency.
- Verified complete test suite across all 9 phases: **74 tests, 404 assertions, 100% passing (0 failures)**.

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
