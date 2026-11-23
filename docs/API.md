# UdhaarHisab Mobile API (v1)

REST API for the shopkeeper mobile app. Every response is JSON.

- **Base URL:** `https://<your-domain>/api/v1`
- **Auth:** send `Authorization: Bearer <token>` on every call except register/login.
- **Headers:** `Accept: application/json` (and `Content-Type: application/json` for JSON bodies).
- **Money:** amounts are strings with 2 decimals (`"1250.00"`), so no floating-point rounding.
- **Dates:** `YYYY-MM-DD`; timestamps are ISO 8601.
- **Who can use it:** shop owners and shop staff of an active shop. Super admins are refused (they use the website's Admin Panel).

**Live demo:** open `/app-demo` in a browser (enabled when `APP_ENV=local` or `MOBILE_DEMO=true`).
Every demo screen has an **API** button that shows the exact request and JSON response.
Demo login after `php artisan db:seed --class=DemoShopSeeder`: **9000000001 / demo1234**.

---

## Screen → API map

| App screen | Calls |
|---|---|
| Login | `POST /auth/login` |
| Register shop | `POST /auth/register` |
| Home | `GET /auth/me`, `GET /dashboard` |
| Customers (Grahak) | `GET /customers?search=&filter=` |
| Add customer | `POST /customers` |
| Customer khata | `GET /customers/{id}`, `GET /customers/{id}/transactions` |
| Add Udhaar / Jama | `GET /customers?search=` (picker), `POST /transactions` |
| Entry detail / Reverse | `GET /transactions/{id}`, `POST /transactions/{id}/reverse` |
| WhatsApp reminder | `GET /customers/{id}/reminder`, `POST /customers/{id}/reminder` |
| Entries register | `GET /transactions?type=` |
| Dukaan (shop) | `GET /auth/me`, `POST /auth/logout` |
| Edit dukaan | `GET /shop`, `PUT /shop` |

---

## Errors

| Status | Meaning | Body |
|---|---|---|
| 401 | Missing/expired token → send user to Login | `{"message": "Unauthenticated."}` |
| 403 | Not allowed (staff doing owner-only action, suspended shop, super admin) | `{"message": "...", "code": "shop_suspended"}` |
| 404 | Record doesn't exist or belongs to another shop | `{"message": "Record not found."}` |
| 422 | Validation failed — show `errors.<field>[0]` under the field | `{"message": "...", "errors": {"amount": ["..."]}}` |
| 429 | Too many requests (login: 5 wrong tries; API: 120/min) | `{"message": "..."}` |

`code` values on 403: `account_inactive`, `super_admin_not_allowed`, `no_shop`, `shop_suspended`, `shop_inactive`.

---

## Auth

### POST /auth/register
Creates a shop + owner with a 30-day free trial and logs in.

```json
{
  "shop_name": "Sharma Kirana Store",
  "owner_name": "Ramesh Sharma",
  "phone": "9876543210",
  "email": null,
  "password": "secret123",
  "password_confirmation": "secret123",
  "terms": true,
  "device_name": "Redmi Note 12"
}
```
**201** → same shape as login.

### POST /auth/login
```json
{ "login": "9876543210", "password": "secret123", "device_name": "Redmi Note 12" }
```
`login` can be the mobile number or email.

**200**
```json
{
  "message": "Login successful.",
  "token": "3|Xk2...",
  "token_type": "Bearer",
  "user": { "id": 4, "name": "Ramesh Sharma", "phone": "9876543210", "email": null,
            "role": "shop_owner", "can_reverse_entries": true, "can_edit_shop": true },
  "shop": { "id": 3, "name": "Sharma Kirana Store", "owner_name": "Ramesh Sharma", "phone": "9876543210",
            "email": null, "address": null, "gstin": null, "currency": "INR", "status": "active", "logo_url": null },
  "subscription": { "plan": "Free 30-Day Trial", "status": "trial", "is_valid": true, "is_expired": false,
                    "is_near_expiry": false, "days_remaining": 30, "customer_count": 0, "customer_limit": 100 }
}
```
Store `token` securely (Android Keystore / iOS Keychain). Each device gets its own token.

### GET /auth/me
Returns `user`, `shop`, `subscription` (same shapes as above). Call on app start to check the token is still valid.

### POST /auth/logout
Revokes the current device's token. **200** `{"message": "Logged out successfully."}`

---

## Dashboard

### GET /dashboard
```json
{
  "totals": {
    "total_due": "3350.00",          // customers owe the shop (show big, red)
    "total_advance": "650.00",       // shop holds customers' advance
    "today_udhaar": "670.00",
    "today_collection": "500.00",
    "total_customers": 5,
    "total_udhaar_given": "6600.00",
    "total_payments_received": "3900.00"
  },
  "recent_transactions": [ /* Transaction, up to 8 */ ],
  "top_due_customers": [ /* Customer, up to 5 */ ],
  "daily_collection_chart": { "labels": ["10 Nov", "..."], "data": [0, 500] }
}
```

---

## Customers

### Customer object
```json
{
  "id": 7, "name": "Suresh Yadav", "phone": "9812300001", "email": null, "address": null, "notes": null,
  "credit_limit": "0.00",
  "balance": "1170.00",
  "balance_status": "due",           // due (red) | advance (green) | settled (grey)
  "balance_label": "Lena hai ₹1170.00",
  "is_archived": false,
  "last_transaction_at": "2026-11-23T10:15:00+05:30",
  "created_at": "2026-11-20T09:00:00+05:30"
}
```

### GET /customers
| Query | Values |
|---|---|
| `search` | name or mobile (partial) |
| `filter` | `all` (default), `due`, `advance`, `settled` |
| `sort` | `latest` (default, recently active first), `name`, `balance_high`, `balance_low` |
| `archived` | `1` to list archived customers |
| `per_page` | 1–50, default 20; use `page` for next pages |

**200** → `{ "data": [Customer], "links": {...}, "meta": { "current_page", "last_page", "total", ... }, "summary": { "total_due", "total_advance", "customer_count" } }`

### POST /customers
```json
{
  "name": "Suresh Yadav",
  "phone": "9812300001",
  "address": "Main Bazar",          // optional
  "email": null, "notes": null,     // optional
  "credit_limit": 0,                // optional
  "opening_balance": 500,           // optional: old baki from paper khata
  "opening_balance_type": "credit"  // credit = customer owes, payment = customer has advance
}
```
**201** → `{ "message": "...", "data": Customer }`.
**422** if the phone already exists in this shop or the plan's customer limit is reached (`errors.name`).

### GET /customers/{id}
**200** → `{ "data": Customer, "totals": { "total_udhaar": "1970.00", "total_jama": "800.00" } }`

### PUT /customers/{id}
Same fields as create, except the opening balance fields. `name` and `phone` are required.

### DELETE /customers/{id}
Archives (soft-deletes) the customer; their entries are kept.

### POST /customers/{id}/restore
Restores an archived customer.

### GET /customers/{id}/transactions
The customer's ledger, newest first. `per_page` 1–50 (default 30). Paginated list of Transaction.

---

## Transactions (entries)

### Transaction object
```json
{
  "id": 19, "number": "TXN-202611-00015",
  "type": "credit",                  // credit | payment | reversal
  "type_label": "Udhaar Diya",       // Udhaar Diya | Jama Mila | Reversal
  "amount": "75.00",
  "balance_after": "-575.00",        // customer balance after this entry
  "payment_mode": "cash",            // cash | upi | bank_transfer | cheque | other
  "date": "2026-11-23",
  "description": "Maggi, biscuit",
  "reference_number": null,
  "bill_image_url": null,
  "is_reversed": false,
  "can_reverse": true,
  "customer": { "id": 6, "name": "Mohammed Arif", "phone": "9812300005" },
  "recorded_by": "Ramesh Sharma",
  "created_at": "2026-11-23T18:40:00+05:30"
}
```

### POST /transactions
Record **Udhaar** (`type: credit`) or **Jama** (`type: payment`).
```json
{
  "customer_id": 6,
  "type": "payment",
  "amount": 300,
  "transaction_date": "2026-11-23",   // today or earlier
  "payment_mode": "upi",              // optional, default cash
  "description": "PhonePe",           // optional
  "reference_number": "UPI123",       // optional
  "allow_advance": false              // payment only: true to accept more than the due amount
}
```
To attach a bill photo, send `multipart/form-data` with a `bill_image` file (jpg/png/webp, max 4 MB).

**201** → `{ "message": "Jama ₹300.00 saved. Naya balance: ₹450.00", "data": Transaction, "customer": Customer }`
Use the returned `customer` to refresh the khata screen without another call.

**422** cases (`errors.amount`): payment bigger than due without `allow_advance`; the same entry submitted twice within 3 seconds.

### GET /transactions
| Query | Values |
|---|---|
| `search` | entry number, reference, customer name or mobile |
| `type` | `credit`, `payment`, `reversal` |
| `payment_mode` | `cash`, `upi`, `bank_transfer`, `cheque`, `other` |
| `from_date`, `to_date` | `YYYY-MM-DD` |
| `per_page` | 1–50, default 20 |

**200** → paginated Transaction list + `"summary": { "total_udhaar", "total_jama" }` (reversed entries excluded).

### GET /transactions/{id}
**200** → `{ "data": Transaction, "reversal_reason": null }`

### POST /transactions/{id}/reverse
Shop **owner** only. Adds a reversal entry; nothing is deleted.
```json
{ "reason": "Galat amount likha" }   // 5–255 characters
```
**200** → `{ "message": "...", "data": Transaction (the reversal), "customer": Customer }`
**403** for staff, **422** if already reversed.

---

## WhatsApp reminder

### GET /customers/{id}/reminder
```json
{
  "customer": { "id": 7, "name": "Suresh Yadav", "phone": "9812300001" },
  "templates": [
    { "key": "standard", "name": "Standard Hinglish (Default)", "language": "Hinglish", "message": "Namaste Suresh Yadav, ..." },
    { "key": "english", ... }, { "key": "pure_hindi", ... }, { "key": "formal_english", ... }
  ]
}
```

### POST /customers/{id}/reminder
```json
{ "template": "standard" }
// or a custom message:
{ "template": "custom", "custom_message": "Bhai, 500 baki hai 🙏" }
```
**200** → `{ "message": "...", "whatsapp_url": "https://wa.me/919812300001?text=...", "text": "..." }`
The app opens `whatsapp_url` (or shares `text` with the share sheet). Limit: 30 per minute.

---

## Shop

### GET /shop
**200** → `{ "data": Shop }`

### PUT /shop
Shop **owner** only.
```json
{ "name": "Sharma Kirana Store", "owner_name": "Ramesh Sharma", "phone": "9876543210",
  "email": null, "address": "Main Bazar", "gstin": null, "currency": "INR" }
```
To upload a logo, send `multipart/form-data` with `_method=PUT` and a `logo` file (max 2 MB) as a **POST** to `/shop`.

---

## Not in the API yet
PDF statements/receipts, CSV/PDF reports, staff management and plan purchase are still website-only.
