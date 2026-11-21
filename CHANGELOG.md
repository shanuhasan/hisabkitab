# Changelog

All notable changes to the HisabKitab SaaS project will be documented in this file.

## [Phase 6] - 2026-11-22
### Added
- Created `WhatsappService` (`app/Services/WhatsappService.php`) providing:
  - Phone number normalization and country code validation (`cleanPhoneNumber()`).
  - Pre-filled reminder message generators for multiple languages (Standard Hinglish, Pure Hindi, Polite English, Formal English).
  - WhatsApp Click-to-Chat URL builder (`buildClickToChatUrl()`).
  - Reminder audit logging (`logReminder()`) with status tracking (`initiated`, `prepared`, etc.).
  - Statement summary share message builder (`generateStatementShareMessage()`).
  - Payment receipt share message builder (`generateReceiptShareMessage()`).
- Created `CurrencyHelper` (`app/Helpers/CurrencyHelper.php`) to convert monetary amounts to Indian currency words (Lakhs, Crores, Rupees, and Paise).
- Created `WhatsappController` (`app/Http/Controllers/WhatsappController.php`) with:
  - `sendReminder`: Handles reminder dispatch, message editing, database audit logging, and redirect to WhatsApp `wa.me` (or JSON response for AJAX).
  - `shareStatement`: Shares account statement summary via WhatsApp.
  - `shareReceipt`: Shares payment receipt confirmation via WhatsApp.
- Added database migration `2026_11_22_000006_update_whatsapp_logs_table.php` updating `whatsapp_logs` table (`status` as varchar, added `template_type` and `sent_by_user_id`).
- Built customer account statement PDF generator (`CustomerController@statementPdf` and `resources/views/customers/statement-pdf.blade.php`) featuring shop details, customer info, statement period, calculated opening balance, transaction ledger, and closing balance.
- Built payment receipt PDF generator (`TransactionController@receiptPdf` and `resources/views/transactions/receipt-pdf.blade.php`) featuring official payment voucher, receipt number, date, amount in figures & words, mode, and remaining balance.
- Updated `resources/views/customers/show.blade.php`:
  - Added interactive **WhatsApp Reminder Modal** with template switcher, live message preview, and pre-send editing.
  - Added WhatsApp Communication History table displaying previous reminders and delivery statuses.
  - Added PDF Statement download and WhatsApp share buttons.
- Updated `resources/views/transactions/show.blade.php` and `resources/views/transactions/ledger-print.blade.php` with PDF download and WhatsApp sharing triggers.
- Registered new routes in `routes/web.php` for statements, receipts, and WhatsApp actions.
- Implemented and passed automated test suite in `tests/Feature/Phase6WhatsAppAndPdfTest.php` (8 tests, 37 assertions). Full test suite now passes 46 tests, 239 assertions (0 failures).

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
