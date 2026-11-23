# Project Architecture — UdhaarHisab SaaS

## 1. System Overview
**UdhaarHisab** is a secure, high-performance, multi-tenant digital udhaar (customer credit and payment ledger) SaaS application designed for Indian small shopkeepers (kirana stores, retail, wholesalers, service providers).

### Core Goals
- Allow shop owners to record credit (udhaar) and payment (jama) quickly on mobile or desktop.
- Prevent revenue leakage and balance calculation discrepancies with strict double-entry-style mathematical integrity.
- Maintain total tenant isolation on the server level so no shop can ever access or leak another shop's data.
- Offer automated WhatsApp reminders, printable PDF customer statements, business reports, and subscription management.

---

## 2. Multi-Tenancy Architecture

### 2.1 Strategy: Single Database with Shared Schema & Row-Level Tenant Isolation
- **Approach**: Single database, shared tables, mandatory `tenant_id` (foreign key to `tenants.id`) on all tenant-specific business entities.
- **Why**: Cost-effective for local Indian shopkeepers, simplified database migrations and backups, high scalability with proper composite indexing `(tenant_id, ...)`.
- **Tenant Context Enforcement**:
  - The active `tenant_id` is derived strictly from the authenticated user's session (`auth()->user()->tenant_id`) and verified against the user's active tenant record.
  - **Zero Trust on Frontend Input**: Requests never accept `tenant_id` from query parameters, route parameters, or request payloads.
  - **Laravel Global Scopes**: Every tenant-owned Eloquent model uses a `BelongsToTenant` trait that automatically applies a `TenantScope` (`WHERE tenant_id = ?`) to all `SELECT`, `UPDATE`, `DELETE` operations.
  - **Model Creating Hook**: Automatically injects `tenant_id = auth()->user()->tenant_id` when new records are persisted.
  - **Laravel Route Model Binding & Policies**: Route parameters (e.g. `Customer $customer`) are resolved through policies (`CustomerPolicy`) that verify `$user->tenant_id === $customer->tenant_id`.

---

## 3. User Roles & Access Control (RBAC)

| Role | Scope | Permissions |
|---|---|---|
| **Super Admin** | Platform-wide | Manages platform subscriptions, plans, tenant status, view platform metrics. Cannot tamper with shop financial ledgers. |
| **Shop Owner** | Single Tenant | Full access to shop profile, customers, transactions, reports, subscription billing, staff management, WhatsApp templates. |
| **Shop Staff** (Future-ready) | Single Tenant | Can create transactions (credit/payment) and view customer balances. Restricted from deleting customers, changing shop settings, or viewing overall profit/loss analytics. |

---

## 4. Financial Rules & Mathematical Invariants

1. **Storage Precision**:
   - Stored in MySQL as `DECIMAL(15, 2)` (e.g., `9999999999999.99`) for balance and transaction amounts.
   - All PHP operations perform high-precision arithmetic (using `bcmath` or standard integer paise formatting) without IEEE 754 floating-point rounding errors.
2. **Transaction Immutability**:
   - Financial ledger entries (`transactions`) are **strictly immutable**.
   - Completed entries are **never edited or hard-deleted**.
   - If an error was made by the shopkeeper, a **Reversal / Correction Transaction** (`type: reversal`) is created, referencing `original_transaction_id` and recording a mandatory reason.
3. **Double-Checked Running Balance & Audit Trail**:
   - Every customer record maintains a cached `current_balance`.
   - Any transaction creation/reversal uses **Pessimistic Locking (`lockForUpdate()`)** inside a **Database Transaction (`DB::transaction(...)`)**.
   - Outstanding balance formula:
     $$\text{New Balance} = \text{Previous Balance} + \text{Credit Amount (Udhaar)} - \text{Payment Amount (Jama)}$$
   - A reconciliation service is provided to recompute and verify customer balances against all historical transactions at any time.

---

## 5. Technology Stack & Directory Structure

- **Framework**: Laravel 12
- **Language**: PHP 8.2+
- **Database**: MySQL 8.0+ / MariaDB 10.4+
- **Frontend**: Blade Components, Bootstrap 5.3, Vanilla JavaScript, Chart.js
- **PDF Generation**: `barryvdh/laravel-dompdf` (DomPDF)
- **Architecture Pattern**:
  - `app/Services/` — Encapsulates core business and financial logic (`TransactionService`, `BalanceService`, `ReportService`, `PdfService`).
  - `app/Http/Requests/` — Strict form validation.
  - `app/Policies/` — Resource authorization and tenant checks.
  - `app/Traits/` — `BelongsToTenant` trait for automatic tenant scoping.
  - `app/Models/` — Eloquent models with typed casts and relationships.

---

## 6. Phased Implementation Roadmap

1. **Phase 1: Project Setup, Multi-Tenancy Core & Authentication**
   - Laravel 12 initialization and environment configuration.
   - Multi-tenant architecture (Tenant model, TenantScope, BelongsToTenant trait).
   - Roles & authentication (Super Admin & Shop Owner login/register).
   - Base layouts (Bootstrap 5, mobile-first responsive sidebar & header).
2. **Phase 2: Shop Profile & Subscription Framework**
   - Shop details (Business Name, GSTIN, Phone, Address, Logo, Currency/Locale).
   - Subscription plans and tenant status gating (Active, Inactive, Expired).
3. **Phase 3: Customer Management Module**
   - Add, edit, list, search, filter customers with pagination.
   - Contact details, address, credit limit, customer notes.
   - Fast AJAX/JS phone & name search.
4. **Phase 4: Core Financial Engine (Udhaar & Payment Entries)**
   - Credit entry (Udhaar Dena / You Gave) & Payment entry (Jama Lena / You Got).
   - Transaction service with DB transactions, pessimistic locking, atomic updates.
   - Transaction validation (non-zero positive amounts, valid dates, receipt attachments).
   - Reversal & correction mechanism.
5. **Phase 5: Customer Ledger & Transaction History**
   - Detailed ledger view per customer with running balance.
   - Filter by date range, transaction type.
   - Bill/receipt image upload and preview.
6. **Phase 6: Dashboard & Analytics**
   - KPI metric cards (Total Udhaar to Receive, Total Jama Received, Net Balance, Today's Activity).
   - Chart.js monthly trend charts (Credit vs Payment).
   - Recent activity list & top defaulters/due accounts.
7. **Phase 7: PDF Statement & WhatsApp Reminder Engine**
   - Customer account statement PDF generation (DomPDF).
   - WhatsApp direct click-to-chat link with pre-composed Hindi/English reminder templates.
   - Reminder audit logging.
8. **Phase 8: Business Reports & Export**
   - Daily/Weekly/Monthly summary reports.
   - Outstanding balance aging report (Due in 30, 60, 90+ days).
   - CSV / Excel export capabilities.
9. **Phase 9: Super Admin Panel**
   - Shop list, status toggling, tenant usage metrics, subscription management.
10. **Phase 10: Security Hardening, Audit & Automated Testing**
    - Feature tests (Tenant isolation, financial calculations, IDOR prevention, concurrency/rollback).
    - Rate limiting, CSRF, XSS protection, sanitization.

