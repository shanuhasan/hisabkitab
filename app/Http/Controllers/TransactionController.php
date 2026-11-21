<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReverseTransactionRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(protected TransactionService $transactionService) {}

    /**
     * Show form to give credit (Udhaar Dena).
     */
    public function creditForm(Request $request): View
    {
        $selectedCustomerId = $request->query('customer_id');
        $selectedCustomer = $selectedCustomerId ? Customer::find($selectedCustomerId) : null;
        $customers = Customer::orderBy('name', 'asc')->get(['id', 'name', 'phone', 'current_balance']);

        return view('transactions.credit', compact('selectedCustomer', 'customers'));
    }

    /**
     * Show form to receive payment (Jama Lena).
     */
    public function paymentForm(Request $request): View
    {
        $selectedCustomerId = $request->query('customer_id');
        $selectedCustomer = $selectedCustomerId ? Customer::find($selectedCustomerId) : null;
        $customers = Customer::orderBy('name', 'asc')->get(['id', 'name', 'phone', 'current_balance']);

        return view('transactions.payment', compact('selectedCustomer', 'customers'));
    }

    /**
     * Store new transaction (Credit or Payment).
     */
    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $customer = Customer::findOrFail($validated['customer_id']);

        // Handle receipt image upload
        if ($request->hasFile('bill_image')) {
            $validated['bill_image_path'] = $request->file('bill_image')->store('bills', 'public');
        }

        $txn = $this->transactionService->recordTransaction($customer, auth()->user(), $validated);

        $typeLabel = $txn->type === 'credit' ? 'Udhaar Dena' : 'Jama Lena';
        $formattedBalance = number_format((float) $txn->running_balance, 2);

        return redirect()->route('customers.show', $customer)
            ->with('success', "{$typeLabel} of ₹ {$txn->amount} recorded successfully. Current Balance: ₹ {$formattedBalance}.");
    }

    /**
     * Shop Transaction History / Len-Den Register.
     */
    public function history(Request $request): View
    {
        $search = trim($request->input('search', ''));
        $type = $request->input('type', 'all');
        $paymentMode = $request->input('payment_mode', 'all');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $query = Transaction::with(['customer', 'user'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        if ($paymentMode !== 'all') {
            $query->where('payment_mode', $paymentMode);
        }

        if (!empty($fromDate)) {
            $query->whereDate('transaction_date', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $query->whereDate('transaction_date', '<=', $toDate);
        }

        // Clone query for totals
        $totalCredit = (clone $query)->where('type', 'credit')->sum('amount');
        $totalPayment = (clone $query)->where('type', 'payment')->sum('amount');

        $transactions = $query->paginate(20)->withQueryString();

        return view('transactions.history', compact(
            'transactions',
            'search',
            'type',
            'paymentMode',
            'fromDate',
            'toDate',
            'totalCredit',
            'totalPayment'
        ));
    }

    /**
     * View transaction details.
     */
    public function show(Transaction $transaction): View
    {
        if ($transaction->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Unauthorized access to transaction.');
        }

        $transaction->load(['customer', 'user', 'reversal.reversalTransaction', 'reversal.user']);

        return view('transactions.show', compact('transaction'));
    }

    /**
     * Reverse an erroneous transaction with audit reason.
     */
    public function reverse(ReverseTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        if ($transaction->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Unauthorized access to transaction.');
        }

        if (!auth()->user()->isShopOwner() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Only shop owners can reverse transactions.');
        }

        $reversalTxn = $this->transactionService->reverseTransaction(
            $transaction,
            auth()->user(),
            $request->validated('reason')
        );

        return redirect()->route('customers.show', $transaction->customer_id)
            ->with('warning', "Transaction {$transaction->transaction_number} was successfully reversed (Reversal Ref: {$reversalTxn->transaction_number}).");
    }

    /**
     * View printable customer ledger.
     */
    public function customerLedger(Request $request, Customer $customer): View
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Unauthorized access to customer ledger.');
        }

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $query = $customer->transactions();

        if (!empty($fromDate)) {
            $query->whereDate('transaction_date', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $query->whereDate('transaction_date', '<=', $toDate);
        }

        $transactions = $query->orderBy('transaction_date', 'asc')->orderBy('id', 'asc')->get();
        $tenant = auth()->user()->tenant;

        return view('transactions.ledger-print', compact('customer', 'transactions', 'tenant', 'fromDate', 'toDate'));
    }
}

