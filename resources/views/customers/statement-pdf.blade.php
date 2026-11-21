<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Account Statement - {{ $customer->name }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #212529;
            margin: 0;
            padding: 15px;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .header table {
            width: 100%;
        }
        .shop-name {
            font-size: 18px;
            font-weight: bold;
            color: #0d6efd;
            margin: 0;
        }
        .shop-details {
            font-size: 10px;
            color: #6c757d;
            margin-top: 3px;
        }
        .statement-title-box {
            text-align: right;
        }
        .statement-title {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            color: #198754;
            margin: 0;
        }
        .statement-date {
            font-size: 10px;
            color: #6c757d;
            margin-top: 3px;
        }
        .customer-info-box {
            width: 100%;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            background-color: #f8f9fa;
            border-collapse: collapse;
        }
        .customer-info-box td {
            padding: 8px 12px;
            font-size: 11px;
        }
        .kpi-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .kpi-box {
            border: 1px solid #dee2e6;
            background-color: #fdfdfd;
            padding: 8px;
            text-align: center;
        }
        .kpi-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 3px;
        }
        .kpi-value {
            font-size: 13px;
            font-weight: bold;
        }
        .text-danger { color: #dc3545; }
        .text-success { color: #198754; }
        .text-primary { color: #0d6efd; }
        .text-dark { color: #212529; }
        
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th {
            background-color: #f1f3f5;
            color: #495057;
            font-size: 10px;
            text-transform: uppercase;
            padding: 6px 8px;
            border: 1px solid #dee2e6;
            text-align: left;
        }
        table.data-table td {
            padding: 5px 8px;
            border: 1px solid #dee2e6;
            font-size: 10px;
        }
        table.data-table tr:nth-child(even) {
            background-color: #fafbfc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-credit { background-color: #f8d7da; color: #842029; }
        .badge-payment { background-color: #d1e7dd; color: #0f5132; }
        .badge-reversal { background-color: #e2e3e5; color: #41464b; }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
            font-size: 9px;
            color: #adb5bd;
            text-align: center;
        }
        .signature-table {
            width: 100%;
            margin-top: 35px;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 15px;
        }
        .signature-line {
            border-top: 1px dashed #6c757d;
            margin-top: 45px;
            text-align: center;
            font-size: 10px;
            color: #495057;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <div class="header">
        <table>
            <tr>
                <td style="width: 55%; vertical-align: top;">
                    <div class="shop-name">{{ $tenant->name }}</div>
                    <div class="shop-details">
                        @if($tenant->address) {{ $tenant->address }}<br> @endif
                        Phone: {{ $tenant->phone ?? 'N/A' }} 
                        @if($tenant->email) &bull; Email: {{ $tenant->email }} @endif
                    </div>
                </td>
                <td style="width: 45%; vertical-align: top;" class="statement-title-box">
                    <div class="statement-title">Customer Account Statement</div>
                    <div class="statement-date">
                        Generated on: {{ date('d M Y, h:i A') }}<br>
                        Period: <strong>{{ $fromDate ? date('d M Y', strtotime($fromDate)) : 'Beginning' }}</strong> to <strong>{{ $toDate ? date('d M Y', strtotime($toDate)) : 'Present' }}</strong>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Customer Information Card -->
    <table class="customer-info-box">
        <tr>
            <td style="width: 50%;">
                <span style="font-size: 9px; text-transform: uppercase; color: #6c757d;">Account Holder</span><br>
                <strong style="font-size: 13px; color: #0d6efd;">{{ $customer->name }}</strong><br>
                <span>Phone: +91 {{ $customer->phone }}</span>
                @if($customer->address)<br><span style="color: #6c757d;">Address: {{ $customer->address }}</span>@endif
            </td>
            <td style="width: 50%; text-align: right;">
                <span style="font-size: 9px; text-transform: uppercase; color: #6c757d;">Statement Summary</span><br>
                <span>Opening Balance: <strong>Rs. {{ number_format((float) $openingBalance, 2) }}</strong></span><br>
                <span>Closing Due: <strong class="{{ (float)$closingBalance > 0 ? 'text-danger' : 'text-success' }}" style="font-size: 14px;">Rs. {{ number_format((float) $closingBalance, 2) }}</strong></span>
            </td>
        </tr>
    </table>

    <!-- Statement KPI Summary -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-box" style="width: 25%;">
                <div class="kpi-label">Opening Balance</div>
                <div class="kpi-value text-dark">Rs. {{ number_format((float) $openingBalance, 2) }}</div>
            </td>
            <td class="kpi-box" style="width: 25%;">
                <div class="kpi-label">Total Udhaar (+)</div>
                <div class="kpi-value text-danger">Rs. {{ number_format((float) $totalCredit, 2) }}</div>
            </td>
            <td class="kpi-box" style="width: 25%;">
                <div class="kpi-label">Total Payments (-)</div>
                <div class="kpi-value text-success">Rs. {{ number_format((float) $totalPayment, 2) }}</div>
            </td>
            <td class="kpi-box" style="width: 25%;">
                <div class="kpi-label">Closing Outstanding Due</div>
                <div class="kpi-value {{ (float)$closingBalance > 0 ? 'text-danger' : 'text-success' }}">
                    Rs. {{ number_format((float) $closingBalance, 2) }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Ledger Entries Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;">Date</th>
                <th style="width: 18%;">Txn Ref</th>
                <th style="width: 12%;">Type</th>
                <th style="width: 14%;">Mode</th>
                <th style="width: 18%;">Notes / Description</th>
                <th style="width: 13%; text-align: right;">Amount (Rs.)</th>
                <th style="width: 13%; text-align: right;">Balance (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            @if((float)$openingBalance != 0)
                <tr style="background-color: #f8f9fa;">
                    <td>{{ $fromDate ? date('d M Y', strtotime($fromDate)) : '-' }}</td>
                    <td><code>OPENING</code></td>
                    <td><span class="badge" style="background-color: #e9ecef; color: #495057;">Opening</span></td>
                    <td>-</td>
                    <td>Balance brought forward</td>
                    <td class="text-right">-</td>
                    <td class="text-right font-weight-bold">Rs. {{ number_format((float) $openingBalance, 2) }}</td>
                </tr>
            @endif

            @forelse($transactions as $txn)
                <tr>
                    <td>{{ $txn->transaction_date->format('d M Y') }}</td>
                    <td><code>{{ $txn->transaction_number }}</code></td>
                    <td>
                        @if($txn->type === 'credit')
                            <span class="badge badge-credit">Udhaar (+)</span>
                        @elseif($txn->type === 'payment')
                            <span class="badge badge-payment">Jama (-)</span>
                        @else
                            <span class="badge badge-reversal">Reversal</span>
                        @endif
                    </td>
                    <td>{{ ucfirst(str_replace('_', ' ', $txn->payment_mode ?? ($txn->type === 'credit' ? 'credit' : 'other'))) }}</td>
                    <td>{{ $txn->description ?: '-' }}</td>
                    <td class="text-right {{ $txn->type === 'credit' ? 'text-danger' : ($txn->type === 'payment' ? 'text-success' : 'text-dark') }}">
                        <strong>
                            {{ $txn->type === 'credit' ? '+' : ($txn->type === 'payment' ? '-' : '') }}Rs. {{ number_format((float) $txn->amount, 2) }}
                        </strong>
                    </td>
                    <td class="text-right">
                        <strong>Rs. {{ number_format((float) $txn->running_balance, 2) }}</strong>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 15px; color: #6c757d;">
                        No transactions recorded during this period.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Signature and Authorization Section -->
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-line">Customer Signature / Acknowledgment</div>
            </td>
            <td>
                <div class="signature-line">Authorized Signatory / For {{ $tenant->name }}</div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        This is a computer-generated account statement &bull; Powered by HisabKitab SaaS &bull; Digital Udhaar Khata System
    </div>

</body>
</html>

