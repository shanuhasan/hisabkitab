@extends('layouts.app')

@section('title', $customer->name . ' — Customer Ledger')
@section('header', $customer->name)
@section('subheader', '+91 ' . $customer->phone . ($customer->address ? ' • ' . $customer->address : ''))

@section('header-actions')
<div class="d-flex flex-wrap gap-2">
    <!-- Transaction Buttons -->
    <a href="{{ route('transactions.credit', ['customer_id' => $customer->id]) }}" class="btn btn-danger fw-semibold shadow-sm">
        <i class="bi bi-arrow-up-right-circle me-1"></i> + Udhaar Dena
    </a>
    <a href="{{ route('transactions.payment', ['customer_id' => $customer->id]) }}" class="btn btn-success fw-semibold shadow-sm">
        <i class="bi bi-arrow-down-left-circle me-1"></i> + Jama Lena
    </a>

    <!-- PDF Statement Download -->
    <a href="{{ route('customers.statement.pdf', $customer) }}" class="btn btn-outline-danger fw-semibold" title="Download PDF Statement">
        <i class="bi bi-file-earmark-pdf me-1"></i> PDF Statement
    </a>

    <!-- Printable Statement -->
    <a href="{{ route('customers.ledger', $customer) }}" target="_blank" class="btn btn-outline-dark fw-semibold" title="Print Ledger View">
        <i class="bi bi-printer me-1"></i> Print View
    </a>

    <!-- Interactive WhatsApp Reminder Button -->
    <button type="button" class="btn btn-success fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#whatsappReminderModal" title="Send WhatsApp Payment Reminder">
        <i class="bi bi-whatsapp me-1"></i> Send WhatsApp Reminder
    </button>

    <!-- Share Statement on WhatsApp -->
    <a href="{{ route('customers.whatsapp.statement', $customer) }}" target="_blank" class="btn btn-outline-success fw-semibold" title="Share Ledger Summary on WhatsApp">
        <i class="bi bi-share me-1"></i> Share Statement
    </a>

    <!-- Direct Phone Call -->
    <a href="tel:+91{{ $customer->phone }}" class="btn btn-outline-secondary fw-semibold">
        <i class="bi bi-telephone-fill me-1"></i> Call
    </a>

    <!-- Edit Customer -->
    @if (!$customer->trashed())
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-primary fw-semibold">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>

        <!-- Archive Customer -->
        <form method="POST" action="{{ route('customers.archive', $customer) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to archive {{ $customer->name }}?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger fw-semibold">
                <i class="bi bi-archive me-1"></i> Archive
            </button>
        </form>
    @else
        <!-- Restore Customer -->
        <form method="POST" action="{{ route('customers.restore', $customer->id) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success fw-semibold">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Restore Customer
            </button>
        </form>
    @endif
</div>
@endsection

@section('content')
<!-- Customer Financial Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Current Outstanding Balance -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-hk h-100 border-start border-4 {{ $customer->current_balance > 0 ? 'border-danger' : ($customer->current_balance < 0 ? 'border-success' : 'border-secondary') }}">
            <div class="card-body">
                <div class="text-muted small fw-semibold mb-1">Current Balance</div>
                @if ($customer->current_balance > 0)
                    <h3 class="fw-bold text-danger mb-1">₹ {{ number_format((float) $customer->current_balance, 2) }}</h3>
                    <span class="badge badge-credit">You'll Get (Udhaar Baki)</span>
                @elseif ($customer->current_balance < 0)
                    <h3 class="fw-bold text-success mb-1">₹ {{ number_format(abs((float) $customer->current_balance), 2) }}</h3>
                    <span class="badge badge-payment">You'll Give (Advance Jama)</span>
                @else
                    <h3 class="fw-bold text-slate-800 mb-1">₹ 0.00</h3>
                    <span class="badge bg-light text-secondary border">Fully Settled (Barabar)</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Total Udhaar Given -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-hk h-100 border-start border-danger border-4">
            <div class="card-body">
                <div class="text-muted small fw-semibold mb-1">Total Udhaar (All-Time)</div>
                <h3 class="fw-bold text-slate-800 mb-1">₹ {{ $customer->total_credit }}</h3>
                <small class="text-muted">Total credit given</small>
            </div>
        </div>
    </div>

    <!-- Total Payments Received -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-hk h-100 border-start border-success border-4">
            <div class="card-body">
                <div class="text-muted small fw-semibold mb-1">Total Jama (All-Time)</div>
                <h3 class="fw-bold text-slate-800 mb-1">₹ {{ $customer->total_payment }}</h3>
                <small class="text-muted">Total payments received</small>
            </div>
        </div>
    </div>

    <!-- Credit Limit & Reminders Sent -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-hk h-100 border-start border-primary border-4">
            <div class="card-body">
                <div class="text-muted small fw-semibold mb-1">Credit Limit &amp; Reminders</div>
                <h4 class="fw-bold text-slate-800 mb-1">
                    {{ $customer->credit_limit > 0 ? '₹ ' . number_format((float) $customer->credit_limit, 2) : 'No Limit' }}
                </h4>
                <small class="text-muted">
                    <i class="bi bi-whatsapp text-success me-1"></i> {{ $customer->whatsappLogs->count() }} reminders recorded
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Ledger Transactions Table -->
<div class="card card-hk mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-journal-text me-1 text-primary"></i> Customer Ledger (Udhaar-Hisab)
            </h6>
            <small class="text-muted">Complete financial timeline and running balances</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('customers.statement.pdf', $customer) }}" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
            </a>
            <a href="{{ route('customers.ledger', $customer) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer me-1"></i> Print
            </a>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4" style="width: 20%;">Date & Ref</th>
                    <th style="width: 32%;">Description & Mode</th>
                    <th class="text-end" style="width: 20%;">Amount</th>
                    <th class="text-end" style="width: 18%;">Running Balance</th>
                    <th class="text-center pe-4" style="width: 10%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $txn)
                    <tr>
                        <td class="ps-4 py-3">
                            <div class="fw-semibold text-dark">{{ $txn->transaction_date->format('d M Y') }}</div>
                            <small class="font-monospace text-muted">{{ $txn->transaction_number }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $txn->description ?? 'Transaction' }}</div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="badge {{ $txn->type === 'credit' ? 'badge-credit' : ($txn->type === 'payment' ? 'badge-payment' : 'bg-secondary') }}">
                                    {{ $txn->type === 'credit' ? 'Udhaar Dena' : ($txn->type === 'payment' ? 'Jama Lena' : 'Reversal') }}
                                </span>
                                <span class="text-muted small">Mode: {{ ucfirst(str_replace('_', ' ', $txn->payment_mode ?? 'credit')) }}</span>
                                @if($txn->reference_number)
                                    <span class="text-muted small">&bull; Ref: {{ $txn->reference_number }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="text-end">
                            @if ($txn->type === 'credit')
                                <div class="amount-credit fs-5">+ ₹ {{ number_format((float) $txn->amount, 2) }}</div>
                            @elseif ($txn->type === 'payment')
                                <div class="amount-payment fs-5">- ₹ {{ number_format((float) $txn->amount, 2) }}</div>
                            @else
                                <div class="text-secondary fs-5">₹ {{ number_format((float) $txn->amount, 2) }}</div>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="fw-bold {{ $txn->running_balance > 0 ? 'text-danger' : ($txn->running_balance < 0 ? 'text-success' : 'text-muted') }}">
                                ₹ {{ number_format((float) $txn->running_balance, 2) }}
                            </div>
                        </td>
                        <td class="text-center pe-4">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('transactions.show', $txn) }}">
                                            <i class="bi bi-eye text-primary me-2"></i> View Details
                                        </a>
                                    </li>
                                    @if($txn->type === 'payment')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('transactions.receipt.pdf', $txn) }}">
                                                <i class="bi bi-file-earmark-pdf text-danger me-2"></i> Download Receipt PDF
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('transactions.whatsapp.receipt', $txn) }}" target="_blank">
                                                <i class="bi bi-whatsapp text-success me-2"></i> Share Receipt (WhatsApp)
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-receipt-cutoff display-4 text-secondary opacity-50 mb-3 d-inline-block"></i>
                                <h6 class="fw-bold text-dark">No Ledger Entries Yet</h6>
                                <p class="small mb-0">No transactions recorded for this customer yet.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($transactions->hasPages())
        <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
            <span class="small text-muted">
                Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} transactions
            </span>
            <div>
                {{ $transactions->links() }}
            </div>
        </div>
    @endif
</div>

<!-- WhatsApp Reminder History Section -->
<div class="card card-hk mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-whatsapp me-1 text-success"></i> WhatsApp Communication History
            </h6>
            <small class="text-muted">Audit log of payment reminders and statements initiated for this customer</small>
        </div>
        <button type="button" class="btn btn-outline-success btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#whatsappReminderModal">
            <i class="bi bi-plus-circle me-1"></i> New Reminder
        </button>
    </div>

    @if($customer->whatsappLogs->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4" style="width: 20%;">Date &amp; Time</th>
                        <th style="width: 15%;">Mobile Sent</th>
                        <th style="width: 15%;">Template</th>
                        <th style="width: 35%;">Message Summary</th>
                        <th class="text-center pe-4" style="width: 15%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customer->whatsappLogs as $log)
                        <tr>
                            <td class="ps-4 small text-dark fw-semibold">
                                {{ $log->created_at ? $log->created_at->format('d M Y, h:i A') : 'N/A' }}
                            </td>
                            <td class="small font-monospace">+{{ $log->phone }}</td>
                            <td class="small">
                                <span class="badge bg-light text-dark border text-uppercase" style="font-size: 0.72rem;">
                                    {{ str_replace('_', ' ', $log->template_type ?? 'reminder') }}
                                </span>
                            </td>
                            <td class="small text-muted text-truncate" style="max-width: 300px;" title="{{ $log->message }}">
                                {{ Str::limit($log->message, 80) }}
                            </td>
                            <td class="text-center pe-4">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="bi bi-check2-circle me-1"></i> {{ ucfirst($log->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="card-body text-center py-4 text-muted">
            <i class="bi bi-chat-left-dots text-secondary opacity-50 fs-2 d-block mb-2"></i>
            <p class="small mb-2">No WhatsApp reminders have been sent to {{ $customer->name }} yet.</p>
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#whatsappReminderModal">
                <i class="bi bi-whatsapp me-1"></i> Send First Reminder
            </button>
        </div>
    @endif
</div>

<!-- WhatsApp Reminder Modal -->
<div class="modal fade" id="whatsappReminderModal" tabindex="-1" aria-labelledby="whatsappReminderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0">
            <form action="{{ route('customers.whatsapp.remind', $customer) }}" method="POST" target="_blank">
                @csrf
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold" id="whatsappReminderModalLabel">
                        <i class="bi bi-whatsapp me-2"></i> Send WhatsApp Payment Reminder
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="alert alert-light border d-flex align-items-center mb-3">
                        <i class="bi bi-person-circle fs-3 text-success me-3"></i>
                        <div>
                            <strong>{{ $customer->name }}</strong> &bull; Outstanding Due: 
                            <span class="text-danger fw-bold">₹ {{ number_format(max(0, (float)$customer->current_balance), 2) }}</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Phone Number with Country Code -->
                        <div class="col-md-6">
                            <label for="reminderPhone" class="form-label fw-semibold small text-muted">Customer Mobile (WhatsApp Number)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted fw-bold">+91</span>
                                <input type="text" name="phone" id="reminderPhone" class="form-control" value="{{ $customer->phone }}" required>
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem;">Indian 10-digit mobile number</small>
                        </div>

                        <!-- Template Selector -->
                        <div class="col-md-6">
                            <label for="templateSelector" class="form-label fw-semibold small text-muted">Message Template / भाषा चुनें</label>
                            <select name="template" id="templateSelector" class="form-select">
                                <option value="standard" selected>Standard Hinglish (Default)</option>
                                <option value="pure_hindi">Hindi (हिंदी में संदेश)</option>
                                <option value="english">English (Polite Reminder)</option>
                                <option value="formal_english">Formal English (Business)</option>
                                <option value="custom">Custom Message (अपना संदेश लिखें)</option>
                            </select>
                        </div>

                        <!-- Editable Message Textarea -->
                        <div class="col-12">
                            <label for="customMessage" class="form-label fw-semibold small text-muted">Message Preview &amp; Edit (भेजने से पहले एडिट करें)</label>
                            <textarea name="custom_message" id="customMessage" class="form-control font-monospace" rows="7" required style="font-size: 0.9rem; line-height: 1.5;"></textarea>
                            <small class="text-muted" style="font-size: 0.75rem;">You can edit any line of this message before clicking Send.</small>
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded mt-3 text-muted small border">
                        <i class="bi bi-info-circle-fill text-primary me-1"></i>
                        Clicking <strong>Send via WhatsApp</strong> will record an initiated reminder in your shop log and automatically open WhatsApp Click-to-Chat with the customer.
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4" onclick="setTimeout(function(){ window.location.reload(); }, 1200);">
                        <i class="bi bi-send-fill me-1"></i> Send via WhatsApp
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const templates = @json($whatsappTemplates);
    const selector = document.getElementById('templateSelector');
    const textarea = document.getElementById('customMessage');

    // Initial populate
    if (selector && textarea && templates[selector.value]) {
        textarea.value = templates[selector.value].message;
    }

    if (selector && textarea) {
        selector.addEventListener('change', function () {
            const val = this.value;
            if (templates[val]) {
                textarea.value = templates[val].message;
            } else if (val === 'custom') {
                textarea.value = "Namaste {{ $customer->name }},\n\n";
                textarea.focus();
            }
        });
    }
});
</script>
@endsection
