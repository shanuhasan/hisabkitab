<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HisabKitab - {{ ucfirst($reportType) }} Report</title>
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
        .report-title-box {
            text-align: right;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            color: #198754;
            margin: 0;
        }
        .report-date {
            font-size: 10px;
            color: #6c757d;
            margin-top: 3px;
        }
        .kpi-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .kpi-box {
            border: 1px solid #dee2e6;
            background-color: #f8f9fa;
            padding: 8px;
            text-align: center;
        }
        .kpi-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 4px;
        }
        .kpi-value {
            font-size: 14px;
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
            padding: 2px 6px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-credit { background-color: #f8d7da; color: #842029; }
        .badge-payment { background-color: #d1e7dd; color: #0f5132; }
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

    <!-- Header Section -->
    <div class="header">
        <table>
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <div class="shop-name">{{ $tenant->name }}</div>
                    <div class="shop-details">
                        @if($tenant->address) {{ $tenant->address }} &bull; @endif
                        Phone: {{ $tenant->phone ?? 'N/A' }} 
                        @if($tenant->email) &bull; Email: {{ $tenant->email }} @endif
                    </div>
                </td>
                <td style="width: 40%; vertical-align: top;" class="report-title-box">
                    <div class="report-title">
                        @if($reportType === 'customer') Customer Summary Report
                        @elseif($reportType === 'outstanding') Outstanding Baki Report
                        @elseif($reportType === 'collection') Payment Collection Report
                        @else Ledger Statement Report
                        @endif
                    </div>
                    <div class="report-date">
                        Generated on: {{ date('d M Y, h:i A') }}<br>
                        @if(!empty($fromDate) || !empty($toDate))
                            Range: {{ $fromDate ? date('d M Y', strtotime($fromDate)) : 'Start' }} to {{ $toDate ? date('d M Y', strtotime($toDate)) : 'Today' }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if($reportType === 'period')
        <!-- Period Report Summary KPIs -->
        <table class="kpi-table">
            <tr>
                <td class="kpi-box" style="width: 25%;">
                    <div class="kpi-label">Total Udhaar Given</div>
                    <div class="kpi-value text-danger">Rs. {{ number_format((float) $data['total_credit'], 2) }}</div>
                </td>
                <td class="kpi-box" style="width: 25%;">
                    <div class="kpi-label">Total Payments Received</div>
                    <div class="kpi-value text-success">Rs. {{ number_format((float) $data['total_payment'], 2) }}</div>
                </td>
                <td class="kpi-box" style="width: 25%;">
                    <div class="kpi-label">Net Balance Change</div>
                    <div class="kpi-value {{ (float)$data['net_change'] >= 0 ? 'text-danger' : 'text-success' }}">
                        {{ (float)$data['net_change'] >= 0 ? '+' : '' }}Rs. {{ number_format((float) $data['net_change'], 2) }}
                    </div>
                </td>
                <td class="kpi-box" style="width: 25%;">
                    <div class="kpi-label">Total Transactions</div>
                    <div class="kpi-value text-dark">{{ $data['transaction_count'] }}</div>
                </td>
            </tr>
        </table>

        <!-- Transactions Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 18%;">Customer</th>
                    <th style="width: 15%;">Txn Ref</th>
                    <th style="width: 10%;">Type</th>
                    <th style="width: 12%;">Mode</th>
                    <th style="width: 18%;">Notes / Reason</th>
                    <th style="width: 15%; text-align: right;">Amount (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['transactions'] as $txn)
                    <tr>
                        <td>{{ $txn->transaction_date->format('d M Y') }}</td>
                        <td><strong>{{ $txn->customer ? $txn->customer->name : 'N/A' }}</strong><br><small style="color:#6c757d;">{{ $txn->customer ? $txn->customer->phone : '' }}</small></td>
                        <td><code>{{ $txn->transaction_number }}</code></td>
                        <td>
                            <span class="badge {{ $txn->type === 'credit' ? 'badge-credit' : 'badge-payment' }}">
                                {{ $txn->type === 'credit' ? 'Udhaar (+)' : 'Jama (-)' }}
                            </span>
                        </td>
                        <td>{{ ucfirst(str_replace('_', ' ', $txn->payment_mode ?? 'credit')) }}</td>
                        <td>{{ $txn->description ?: '-' }}</td>
                        <td class="text-right <strong>{{ $txn->type === 'credit' ? 'text-danger' : 'text-success' }}">
                            {{ $txn->type === 'credit' ? '+' : '-' }}Rs. {{ number_format((float) $txn->amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 15px; color: #6c757d;">No transactions recorded for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif($reportType === 'outstanding')
        <!-- Outstanding Customers Summary KPIs -->
        <table class="kpi-table">
            <tr>
                <td class="kpi-box" style="width: 50%;">
                    <div class="kpi-label">Total Outstanding Baki (Market Receivable)</div>
                    <div class="kpi-value text-danger">Rs. {{ number_format((float) $data['total_outstanding'], 2) }}</div>
                </td>
                <td class="kpi-box" style="width: 50%;">
                    <div class="kpi-label">Customers with Due Balance</div>
                    <div class="kpi-value text-dark">{{ $data['count'] }} Customers</div>
                </td>
            </tr>
        </table>

        <!-- Outstanding List -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 8%;">#</th>
                    <th style="width: 30%;">Customer Name</th>
                    <th style="width: 20%;">Mobile Number</th>
                    <th style="width: 22%;">Last Transaction Date</th>
                    <th style="width: 20%; text-align: right;">Outstanding Due (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['customers'] as $idx => $cust)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td><strong>{{ $cust->name }}</strong></td>
                        <td>{{ $cust->phone }}</td>
                        <td>{{ $cust->last_transaction_at ? $cust->last_transaction_at->format('d M Y') : 'No Transactions' }}</td>
                        <td class="text-right text-danger font-weight-bold">
                            <strong>Rs. {{ number_format((float) $cust->current_balance, 2) }}</strong>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 15px; color: #198754;">Shandaar! Sabhi grahakon ka hisab barabar hai (No outstanding due).</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif($reportType === 'customer')
        <!-- Customer Summary KPIs -->
        <table class="kpi-table">
            <tr>
                <td class="kpi-box" style="width: 33%;">
                    <div class="kpi-label">Total Market Receivable</div>
                    <div class="kpi-value text-danger">Rs. {{ number_format((float) $data['total_receivable'], 2) }}</div>
                </td>
                <td class="kpi-box" style="width: 33%;">
                    <div class="kpi-label">Total Advance Balance</div>
                    <div class="kpi-value text-success">Rs. {{ number_format((float) $data['total_advance'], 2) }}</div>
                </td>
                <td class="kpi-box" style="width: 34%;">
                    <div class="kpi-label">Total Customers</div>
                    <div class="kpi-value text-dark">{{ $data['count'] }}</div>
                </td>
            </tr>
        </table>

        <!-- Customer Summary Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Customer Name</th>
                    <th style="width: 15%;">Mobile</th>
                    <th style="width: 15%; text-align: right;">Total Udhaar</th>
                    <th style="width: 15%; text-align: right;">Total Jama</th>
                    <th style="width: 15%; text-align: right;">Net Balance</th>
                    <th style="width: 15%;">Last Activity</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['customers'] as $cust)
                    <tr>
                        <td><strong>{{ $cust['name'] }}</strong></td>
                        <td>{{ $cust['phone'] }}</td>
                        <td class="text-right text-danger">Rs. {{ number_format((float) $cust['total_credit'], 2) }}</td>
                        <td class="text-right text-success">Rs. {{ number_format((float) $cust['total_payment'], 2) }}</td>
                        <td class="text-right">
                            @if((float)$cust['current_balance'] > 0)
                                <strong class="text-danger">Rs. {{ number_format((float) $cust['current_balance'], 2) }} Dr</strong>
                            @elseif((float)$cust['current_balance'] < 0)
                                <strong class="text-success">Rs. {{ number_format(abs((float) $cust['current_balance']), 2) }} Cr</strong>
                            @else
                                <span class="text-dark">Rs. 0.00</span>
                            @endif
                        </td>
                        <td>{{ $cust['last_transaction_at'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 15px; color: #6c757d;">No customer records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif($reportType === 'collection')
        <!-- Collection Report Summary KPIs -->
        <table class="kpi-table">
            <tr>
                <td class="kpi-box" style="width: 50%;">
                    <div class="kpi-label">Total Payments Collected</div>
                    <div class="kpi-value text-success">Rs. {{ number_format((float) $data['total_collected'], 2) }}</div>
                </td>
                <td class="kpi-box" style="width: 50%;">
                    <div class="kpi-label">Payment Entries</div>
                    <div class="kpi-value text-dark">{{ $data['count'] }} Received</div>
                </td>
            </tr>
        </table>

        <!-- Collections Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 22%;">Customer</th>
                    <th style="width: 18%;">Receipt No</th>
                    <th style="width: 14%;">Mode</th>
                    <th style="width: 18%;">Notes</th>
                    <th style="width: 16%; text-align: right;">Amount (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['payments'] as $payment)
                    <tr>
                        <td>{{ $payment->transaction_date->format('d M Y') }}</td>
                        <td><strong>{{ $payment->customer ? $payment->customer->name : 'N/A' }}</strong><br><small style="color:#6c757d;">{{ $payment->customer ? $payment->customer->phone : '' }}</small></td>
                        <td><code>{{ $payment->transaction_number }}</code></td>
                        <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_mode ?? 'cash')) }}</td>
                        <td>{{ $payment->description ?: '-' }}</td>
                        <td class="text-right text-success font-weight-bold">
                            <strong>Rs. {{ number_format((float) $payment->amount, 2) }}</strong>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 15px; color: #6c757d;">No payments recorded in this duration.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <!-- Footer -->
    <div class="footer">
        Generated securely by HisabKitab SaaS &bull; Digital Udhaar &amp; Khata Management for Indian Businesses &bull; Confidential
    </div>

</body>
</html>
