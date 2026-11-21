<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Services\WhatsappService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
        protected WhatsappService $whatsappService
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
        $validated = $request->validated();
        $user = auth()->user();

        $customer = DB::transaction(function () use ($validated, $user) {
            $openingBalance = isset($validated['opening_balance']) && (float) $validated['opening_balance'] > 0
                ? number_format((float) $validated['opening_balance'], 2, '.', '')
                : null;
            $openingType = $validated['opening_balance_type'] ?? 'credit';

            // Initial balance calculation
            $initialBalance = '0.00';
            if ($openingBalance !== null) {
                $initialBalance = $openingType === 'credit'
                    ? $openingBalance
                    : '-' . $openingBalance;
            }

            // 1. Create Customer record (BelongsToTenant trait injects tenant_id)
            $customer = Customer::create([
                'tenant_id' => $user->tenant_id,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'credit_limit' => $validated['credit_limit'] ?? 0.00,
                'current_balance' => $initialBalance,
                'status' => 'active',
                'last_transaction_at' => $openingBalance !== null ? now() : null,
            ]);

            // 2. If opening balance exists, record audited opening transaction
            if ($openingBalance !== null) {
                Transaction::create([
                    'tenant_id' => $user->tenant_id,
                    'customer_id' => $customer->id,
                    'user_id' => $user->id,
                    'transaction_number' => $this->transactionService->generateTransactionNumber($user->tenant_id),
                    'type' => $openingType, // 'credit' = Purana Udhaar, 'payment' = Purana Advance
                    'amount' => $openingBalance,
                    'running_balance' => $initialBalance,
                    'payment_mode' => 'other',
                    'transaction_date' => now()->toDateString(),
                    'description' => $openingType === 'credit'
                        ? 'Opening Balance (Purana Udhaar / Baki)'
                        : 'Opening Advance (Peshi Jama)',
                    'is_reversed' => false,
                ]);
            }

            return $customer;
        });

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

        // Load customer transactions
        $transactions = $customer->transactions()->paginate(20);

        return view('customers.show', compact('customer', 'tenant', 'whatsappUrl', 'transactions'));
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

        $customer->status = 'inactive';
        $customer->save();
        $customer->delete();

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
}

