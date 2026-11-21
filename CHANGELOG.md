# Changelog

All notable changes to the HisabKitab SaaS project will be documented in this file.

## [Phase 4] - 2026-11-22
### Added
- Implemented core financial engine (`TransactionService`) using atomic DB transactions and pessimistic row locking (`lockForUpdate()`).
- High-precision monetary arithmetic strictly using `bcmath` (`DECIMAL(15,2)` precision).
- Implemented Udhaar Dena (credit entry) module (`/transactions/credit`) with bill reference and photo attachment.
- Implemented Jama Lena (payment entry) module (`/transactions/payment`) with Cash, UPI, Bank Transfer, Cheque modes and balance validation.
- Added payment validation against customer outstanding balance with advance option and partial payment support.
- Built immutable audit trail with reversal transactions (`type: reversal`) and audit logs in `transaction_reversals`.
- Built Global Shop Len-Den Register (`/transactions/history`) with customer search, date range filtering, type filtering, payment mode filtering, and totals.
- Created individual transaction details view (`/transactions/{transaction}`) with receipt photo preview and reversal info.
- Built dedicated printable Customer Account Statement view (`/customers/{customer}/ledger`) with signature lines and print CSS.
- Built Blade views:
  - `resources/views/transactions/credit.blade.php`
  - `resources/views/transactions/payment.blade.php`
  - `resources/views/transactions/history.blade.php`
  - `resources/views/transactions/show.blade.php`
  - `resources/views/transactions/ledger-print.blade.php`
- Implemented and passed automated test suite in `tests/Feature/Phase4FinancialLedgerAndTransactionsTest.php` (9 tests, 40 assertions).

## [Phase 3] - 2026-11-22
### Added
- Created complete Customer Management module (`CustomerController`, `StoreCustomerRequest`, `UpdateCustomerRequest`).
- Implemented opening balance feature: creates an audited ledger entry in `transactions` with type `credit` or `payment`.
- Enforced per-tenant phone uniqueness on customers (`tenant_id + phone`).
- Added Customer listing with real-time search by name/mobile, balance filters, status toggle, and sorting.
- Added Customer details page, edit form, archive and restore functionality.

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
