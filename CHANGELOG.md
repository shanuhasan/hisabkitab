# Changelog

All notable changes to the HisabKitab SaaS project will be documented in this file.

## [Phase 7] - 2026-11-22
### Added
- Created official SaaS subscription plans (`PlanSeeder`):
  - **Free Plan**: ₹0, 25 customers, basic ledger & basic reports.
  - **Basic Plan**: ₹99/mo, 200 customers, PDF statements & WhatsApp reminders.
  - **Pro Plan**: ₹199/mo, unlimited customers, CSV exports, advanced reports & priority support.
- Added database migration `2026_11_22_000007_create_subscription_management_tables.php`:
  - Added `slug` and `description` to `plans`.
  - Added `trial_ends_at` and `grace_ends_at` to `subscriptions`.
  - Created `subscription_payments` table for payment audit trails.
- Created `SubscriptionPayment` model (`app/Models/SubscriptionPayment.php`).
- Created `SubscriptionService` (`app/Services/SubscriptionService.php`) providing:
  - Razorpay order creation (`createRazorpayOrder`).
  - Server-side HMAC-SHA256 signature verification and atomic activation (`verifyAndActivatePayment`).
  - Webhook processing with HMAC-SHA256 signature verification and idempotency protection (`processWebhook`).
  - Customer quota check (`canAddCustomer`) and feature gate check (`canAccessFeature`).
  - Subscription status metadata with grace period detection.
- Created `EnforceSubscriptionLimits` middleware (`app/Http/Middleware/EnforceSubscriptionLimits.php`):
  - Blocks expired shops past grace period from executing business operations.
  - Enforces feature gates (`csv_exports`, `pdf_statements`).
  - Registered `'subscription'` middleware alias in `bootstrap/app.php`.
- Exempted `/webhook/razorpay` from CSRF verification in `bootstrap/app.php`.
- Created `SubscriptionController` (`app/Http/Controllers/SubscriptionController.php`):
  - Subscription portal view with current status, customer quota progress bar, and pricing tiers.
  - Order creation and payment signature verification endpoints.
  - Razorpay webhook listener.
- Created Super Admin Plan Management Controller (`app/Http/Controllers/Admin/PlanController.php`) and views (`resources/views/admin/plans/index.blade.php`, `resources/views/admin/plans/edit.blade.php`).
- Built customer quota enforcement in `CustomerController@store`: prevents adding customers beyond plan limits.
- Built feature restrictions in `ReportController@exportCsv` (Pro plan required) and `CustomerController@statementPdf` (Basic/Pro plan required).
- Built frontend view `resources/views/subscription/index.blade.php` with Razorpay checkout integration, status card, pricing grid, and payment history.
- Implemented and passed automated test suite in `tests/Feature/Phase7SubscriptionManagementTest.php` (9 tests, 54 assertions). Full test suite now passes 55 tests, 293 assertions (0 failures).

## [Phase 6] - 2026-11-22
### Added
- Created `WhatsappService` (`app/Services/WhatsappService.php`) with multi-language reminder templates and click-to-chat URL generator.
- Created `CurrencyHelper` (`app/Helpers/CurrencyHelper.php`) to convert monetary amounts to Indian currency words.
- Created `WhatsappController` (`app/Http/Controllers/WhatsappController.php`).
- Added database migration updating `whatsapp_logs` table.
- Built customer account statement PDF generator (`CustomerController@statementPdf` and `statement-pdf.blade.php`).
- Built payment receipt PDF generator (`TransactionController@receiptPdf` and `receipt-pdf.blade.php`).
- Updated `show.blade.php` with interactive WhatsApp Reminder Modal and communication history table.
- Implemented and passed automated test suite in `tests/Feature/Phase6WhatsAppAndPdfTest.php` (8 tests, 37 assertions).

## [Phase 5] - 2026-11-22
### Added
- Created `ReportService` (`app/Services/ReportService.php`) providing dashboard metrics, period reports, customer summaries, and collection reports.
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
