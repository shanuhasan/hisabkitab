<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt - {{ $transaction->transaction_number }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #212529;
            margin: 0;
            padding: 20px;
            line-height: 1.5;
        }
        .receipt-card {
            border: 2px solid #198754;
            padding: 20px;
            border-radius: 6px;
        }
        .header {
            border-bottom: 2px solid #198754;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .header table {
            width: 100%;
        }
        .shop-name {
            font-size: 20px;
            font-weight: bold;
            color: #0d6efd;
            margin: 0;
        }
        .shop-details {
            font-size: 10px;
            color: #6c757d;
            margin-top: 3px;
        }
        .receipt-title-box {
            text-align: right;
        }
        .receipt-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            color: #198754;
            margin: 0;
        }
        .receipt-no {
            font-size: 11px;
            font-weight: bold;
            color: #212529;
            margin-top: 3px;
        }
        .receipt-date {
            font-size: 10px;
            color: #6c757d;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        .info-label {
            width: 30%;
            font-weight: bold;
            color: #495057;
            text-transform: uppercase;
            font-size: 10px;
        }
        .info-value {
            width: 70%;
            font-size: 11px;
        }
        
        .amount-highlight-box {
            background-color: #d1e7dd;
            border: 1px solid #a3cfbb;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .amount-highlight-box table {
            width: 100%;
        }
        .amount-figure {
            font-size: 20px;
            font-weight: bold;
            color: #0f5132;
        }
        .amount-words {
            font-size: 11px;
            font-style: italic;
            color: #0f5132;
            margin-top: 3px;
        }

        .balance-summary-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        .balance-summary-box table {
            width: 100%;
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
        .footer {
            margin-top: 25px;
            border-top: 1px solid #dee2e6;
            padding-top: 8px;
            font-size: 9px;
            color: #adb5bd;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="receipt-card">
        <!-- Header -->
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
                    <td style="width: 45%; vertical-align: top;" class="receipt-title-box">
                        <div class="receipt-title">Payment Receipt / जमा रसीद</div>
                        <div class="receipt-no">Receipt #: {{ $transaction->transaction_number }}</div>
                        <div class="receipt-date">Date: {{ $transaction->transaction_date->format('d M Y') }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Amount Box -->
        <div class="amount-highlight-box">
            <table>
                <tr>
                    <td>
                        <span style="font-size: 10px; text-transform: uppercase; color: #0f5132;">Amount Received (रकम प्राप्त):</span>
                        <div class="amount-figure">Rs. {{ number_format((float) $transaction->amount, 2) }}</div>
                        <div class="amount-words">In words: <strong>{{ $amountInWords }}</strong></div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Transaction Details Table -->
        <table class="info-table">
            <tr>
                <td class="info-label">Received From (ग्राहक का नाम)</td>
                <td class="info-value">
                    <strong style="font-size: 12px; color: #0d6efd;">{{ $customer ? $customer->name : 'N/A' }}</strong>
                </td>
            </tr>
            <tr>
                <td class="info-label">Customer Mobile</td>
                <td class="info-value">+91 {{ $customer ? $customer->phone : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">Payment Mode (भुगतान माध्यम)</td>
                <td class="info-value">
                    <strong style="text-transform: uppercase;">{{ str_replace('_', ' ', $transaction->payment_mode ?? 'Cash') }}</strong>
                </td>
            </tr>
            @if($transaction->reference_number)
            <tr>
                <td class="info-label">UPI / Bank / Cheque Ref</td>
                <td class="info-value"><code>{{ $transaction->reference_number }}</code></td>
            </tr>
            @endif
            <tr>
                <td class="info-label">Notes / Description</td>
                <td class="info-value">{{ $transaction->description ?: 'Payment received towards customer udhaar account.' }}</td>
            </tr>
            <tr>
                <td class="info-label">Received By (Operator)</td>
                <td class="info-value">{{ $transaction->user ? $transaction->user->name : 'Shop Owner' }}</td>
            </tr>
        </table>

        <!-- Running Balance Summary -->
        <div class="balance-summary-box">
            <table>
                <tr>
                    <td style="width: 50%;">
                        <span style="font-size: 10px; color: #6c757d;">Remaining Account Balance:</span><br>
                        <strong style="font-size: 13px;" class="{{ (float)$transaction->running_balance > 0 ? 'text-danger' : 'text-success' }}">
                            Rs. {{ number_format((float) $transaction->running_balance, 2) }} 
                            {{ (float)$transaction->running_balance > 0 ? '(Due Pending)' : '(Fully Settled / Advance)' }}
                        </strong>
                    </td>
                    <td style="width: 50%; text-align: right;">
                        <span style="font-size: 10px; color: #6c757d;">Payment Status:</span><br>
                        <strong style="color: #198754; font-size: 13px;">Confirmed &amp; Credited</strong>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Signature Section -->
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
            UdhaarHisab SaaS &bull; Digital Khata &amp; Udhaar Ledger System &bull; Computer Generated Official Receipt
        </div>
    </div>

</body>
</html>

