<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $customer->name }} — Customer Account Statement</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            color: #0f172a;
            background-color: #f8fafc;
        }
        .statement-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 30px;
            max-width: 900px;
            margin: 30px auto;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .text-credit { color: #dc2626; font-weight: 700; }
        .text-payment { color: #16a34a; font-weight: 700; }
        @media print {
            body { background-color: #ffffff; }
            .statement-card { border: none; box-shadow: none; padding: 0; margin: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <!-- Top Action Bar (No Print) -->
    <div class="container text-center py-3 no-print">
        <button onclick="window.print()" class="btn btn-primary btn-sm px-4 fw-semibold me-2 shadow-sm">
            <i class="bi bi-printer me-1"></i> Print Statement
        </button>
        <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary btn-sm">
            &larr; Back to Customer
        </a>
    </div>

    <div class="statement-card">
        <!-- Shop Header -->
        <div class="d-flex justify-content-between align-items-start pb-4 border-bottom mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">{{ $tenant->name }}</h3>
                <div class="text-muted small">Proprietor: {{ $tenant->owner_name }}</div>
                @if ($tenant->address)
                    <div class="text-muted small">{{ $tenant->address }}</div>
                @endif
                <div class="text-muted small">Phone: +91 {{ $tenant->phone }}</div>
                @if ($tenant->gstin)
                    <div class="text-muted small">GSTIN: {{ $tenant->gstin }}</div>
                @endif
            </div>
            <div class="text-end">
                <span class="badge bg-primary px-3 py-2 fs-6 mb-2">CUSTOMER STATEMENT</span>
                <div class="small text-muted">Generated: {{ now()->format('d M Y, h:i A') }}</div>
                @if ($fromDate || $toDate)
                    <div class="small fw-semibold text-secondary">
                        Period: {{ $fromDate ? date('d M Y', strtotime($fromDate)) : 'Start' }} to {{ $toDate ? date('d M Y', strtotime($toDate)) : 'Today' }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Customer Summary -->
        <div class="row bg-light p-3 rounded border mb-4">
            <div class="col-sm-6">
                <div class="text-muted small text-uppercase fw-semibold">Customer Details</div>
                <h5 class="fw-bold mb-1">{{ $customer->name }}</h5>
                <div class="small">Phone: +91 {{ $customer->phone }}</div>
                @if ($customer->address)
                    <div class="small text-muted">Address: {{ $customer->address }}</div>
                @endif
            </div>
            <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                <div class="text-muted small text-uppercase fw-semibold">Outstanding Net Balance</div>
                <div class="h3 fw-bold {{ $customer->current_balance > 0 ? 'text-credit' : ($customer->current_balance < 0 ? 'text-payment' : 'text-dark') }} mb-0">
                    ₹ {{ number_format((float) $customer->current_balance, 2) }}
                </div>
                <span class="badge {{ $customer->current_balance > 0 ? 'bg-danger-subtle text-danger' : ($customer->current_balance < 0 ? 'bg-success-subtle text-success' : 'bg-light text-dark') }} border">
                    {{ $customer->current_balance > 0 ? "You'll Get (Udhaar)" : ($customer->current_balance < 0 ? "You'll Give (Advance)" : 'Settled') }}
                </span>
            </div>
        </div>

        <!-- Transaction Table -->
        <table class="table table-bordered table-sm align-middle mb-4">
            <thead class="table-light text-uppercase small text-muted">
                <tr>
                    <th style="width: 15%;">Date</th>
                    <th style="width: 20%;">Txn Ref</th>
                    <th style="width: 30%;">Details / Description</th>
                    <th class="text-end" style="width: 17%;">Amount (₹)</th>
                    <th class="text-end" style="width: 18%;">Balance (₹)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $txn)
                    <tr>
                        <td class="small">{{ $txn->transaction_date->format('d/m/Y') }}</td>
                        <td class="small font-monospace">{{ $txn->transaction_number }}</td>
                        <td class="small">
                            <div>{{ $txn->description ?: 'Transaction' }}</div>
                            <span class="badge {{ $txn->type === 'credit' ? 'text-danger' : ($txn->type === 'payment' ? 'text-success' : 'text-muted') }} p-0" style="font-size: 0.75rem;">
                                {{ $txn->type === 'credit' ? '+ Udhaar Dena' : ($txn->type === 'payment' ? '- Jama Lena' : 'Reversal') }}
                            </span>
                        </td>
                        <td class="text-end fw-semibold {{ $txn->type === 'credit' ? 'text-credit' : ($txn->type === 'payment' ? 'text-payment' : '') }}">
                            {{ $txn->type === 'credit' ? '+' : ($txn->type === 'payment' ? '-' : '') }} {{ number_format((float) $txn->amount, 2) }}
                        </td>
                        <td class="text-end fw-bold {{ $txn->running_balance > 0 ? 'text-credit' : ($txn->running_balance < 0 ? 'text-payment' : '') }}">
                            {{ number_format((float) $txn->running_balance, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted small">No transactions found for the selected period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Signatures & Disclaimer -->
        <div class="row pt-5 mt-4 border-top">
            <div class="col-6 text-center">
                <div class="pt-4 border-top d-inline-block px-4 small text-muted">
                    Customer Signature
                </div>
            </div>
            <div class="col-6 text-center">
                <div class="pt-4 border-top d-inline-block px-4 small text-muted">
                    For {{ $tenant->name }} (Authorized Signatory)
                </div>
            </div>
        </div>

        <div class="text-center text-muted small mt-4 pt-2 border-top" style="font-size: 0.75rem;">
            This is a computer-generated account statement managed digitally on HisabKitab SaaS.
        </div>
    </div>
</body>
</html>

