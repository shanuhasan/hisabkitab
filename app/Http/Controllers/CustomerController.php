<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\CustomerService;
use App\Services\SubscriptionService;
use App\Services\TransactionService;
use App\Services\WhatsappService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
        protected WhatsappService $whatsappService,
        protected SubscriptionService $subscriptionService,
        protected CustomerService $customerService
    ) {}

    /**
     * Display a listing of customers with search, filters, and pagination.
     */
    public function index(Request $request): View
    {
        $tenantId = auth()->user()->tenant_id;
        $search = trim($request->input('search', ''));
        $balanceFilter = $request->input('balance', 'all');
        $archiveFilter = $request->input('status', 'active');
        $sortBy = $request->input('sort', 'latest');

        // Base query - automatically scoped by TenantScope
        $query = $archiveFilter === 'archived'
            ? Customer::onlyTrashed()
            : Customer::withoutTrashed();

        // Search by name or 10-digit mobile number
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by balance condition
        if ($balanceFilter === 'receivable') {
            $query->where('current_balance', '>', 0);
        } elseif ($balanceFilter === 'advance') {
            $query->where('current_balance', '<', 0);
        } elseif ($balanceFilter === 'settled') {
            $query->where('current_balance', '=', 0);
        }

        // Sorting
        match ($sortBy) {
            'name_asc' => $query->orderBy('name', 'asc'),
            'balance_desc' => $query->orderBy('current_balance', 'desc'),
            'balance_asc' => $query->orderBy('current_balance', 'asc'),
            default => $query->orderBy('id', 'desc'),
        };

        $customers = $query->paginate(15)->withQueryString();

        // Summary metrics for the shop's customers
        $totalReceivable = Customer::where('current_balance', '>', 0)->sum('current_balance');
        $totalAdvance = Customer::where('current_balance', '<', 0)->sum('current_balance');
        $totalCustomerCount = Customer::count();
        $archivedCount = Customer::onlyTrashed()->count();

        return view('customers.index', compact(
            'customers',
            'search',
            'balanceFilter',
            'archiveFilter',
            'sortBy',
            'totalReceivable',
            'totalAdvance',
            'totalCustomerCount',
            'archivedCount'
        ));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create(): View
    {
        return view('customers.create');
    }

    /**
     * Store a newly created customer in database.
     * Optionally creates an audited opening balance entry in the ledger.
     */
    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = $this->customerService->createCustomer($request->validated(), auth()->user());

        return redirect()->route('customers.show', $customer)
            ->with('success', "Grahak '{$customer->name}' safaltapoorvak jod diya gaya hai.");
    }

    /**
     * Display the specified customer details and ledger summary.
     */
    public function show(Customer $customer): View
    {
        // Enforce strict tenant ownership
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Unauthorized access to customer.');
        }

        $tenant = auth()->user()->tenant;
        $whatsappUrl = $this->whatsappService->generateReminderUrl($customer, $tenant);
        $whatsappTemplates = $this->whatsappService->getReminderTemplates($customer, $tenant);
        $whatsappLogs = $customer->whatsappLogs()->take(10)->get();

        // Load customer transactions
        $transactions = $customer->transactions()->paginate(20);

        return view('customers.show', compact('customer', 'tenant', 'whatsappUrl', 'whatsappTemplates', 'whatsappLogs', 'transactions'));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer): View
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Unauthorized access to customer.');
        }

        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Unauthorized access to customer.');
        }

        $validated = $request->validated();

        $customer->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'credit_limit' => $validated['credit_limit'] ?? 0.00,
        ]);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Customer details updated successfully.');
    }

    /**
     * Archive the specified customer (Soft Delete & status inactive).
     */
    public function archive(Customer $customer): RedirectResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Unauthorized access to customer.');
        }

        $this->customerService->archiveCustomer($customer);

        return redirect()->route('customers.index')
            ->with('success', "Customer '{$customer->name}' has been archived.");
    }

    /**
     * Restore an archived customer.
     */
    public function restore(int $id): RedirectResponse
    {
        $customer = Customer::onlyTrashed()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($id);

        $customer->restore();
        $customer->status = 'active';
        $customer->save();

        return redirect()->route('customers.show', $customer)
            ->with('success', "Customer '{$customer->name}' has been restored successfully.");
    }

    /**
     * Generate and download professional Customer Account Statement PDF.
     */
    public function statementPdf(Request $request, Customer $customer): Response
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Unauthorized access to customer statement.');
        }

        $tenant = auth()->user()->tenant;
        if ($tenant && !$this->subscriptionService->canAccessFeature($tenant, 'pdf_statements')) {
            abort(403, 'PDF Statements are not available on your current plan. Please upgrade to Basic or Pro Plan.');
        }

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        // Calculate opening balance prior to fromDate
        $openingBalance = '0.00';
        if (!empty($fromDate)) {
            $prevTxn = $customer->transactions()
                ->whereDate('transaction_date', '<', $fromDate)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            if ($prevTxn) {
                $openingBalance = (string) $prevTxn->running_balance;
            }
        }

        // Ledger entries within the date period
        $query = $customer->transactions();
        if (!empty($fromDate)) {
            $query->whereDate('transaction_date', '>=', $fromDate);
        }
        if (!empty($toDate)) {
            $query->whereDate('transaction_date', '<=', $toDate);
        }

        $transactions = $query->orderBy('transaction_date', 'asc')->orderBy('id', 'asc')->get();

        $totalCredit = $transactions->where('type', 'credit')->where('is_reversed', false)->sum('amount');
        $totalPayment = $transactions->where('type', 'payment')->where('is_reversed', false)->sum('amount');

        $closingBalance = $transactions->isNotEmpty()
            ? (string) $transactions->last()->running_balance
            : $openingBalance;

        $pdf = Pdf::loadView('customers.statement-pdf', [
            'customer' => $customer,
            'tenant' => $tenant,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'openingBalance' => $openingBalance,
            'transactions' => $transactions,
            'totalCredit' => number_format((float) $totalCredit, 2, '.', ''),
            'totalPayment' => number_format((float) $totalPayment, 2, '.', ''),
            'closingBalance' => number_format((float) $closingBalance, 2, '.', ''),
        ]);

        $fileName = sprintf(
            'Statement-%s-%s.pdf',
            preg_replace('/[^A-Za-z0-9]/', '', $customer->name),
            date('Ymd')
        );

        return $pdf->download($fileName);
    }
}

