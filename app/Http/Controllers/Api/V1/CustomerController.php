<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\TransactionResource;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function __construct(protected CustomerService $customerService) {}

    /**
     * GET /api/v1/customers — search & filter the shop's customers.
     *
     * Query: search, filter (all|due|advance|settled), sort (latest|name|balance_high|balance_low),
     *        archived (0|1), per_page (max 50)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'filter' => ['nullable', 'in:all,due,advance,settled'],
            'sort' => ['nullable', 'in:latest,name,balance_high,balance_low'],
            'archived' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = !empty($validated['archived']) ? Customer::onlyTrashed() : Customer::query();

        if ($search = trim($validated['search'] ?? '')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        match ($validated['filter'] ?? 'all') {
            'due' => $query->where('current_balance', '>', 0),
            'advance' => $query->where('current_balance', '<', 0),
            'settled' => $query->where('current_balance', '=', 0),
            default => null,
        };

        match ($validated['sort'] ?? 'latest') {
            'name' => $query->orderBy('name'),
            'balance_high' => $query->orderBy('current_balance', 'desc'),
            'balance_low' => $query->orderBy('current_balance', 'asc'),
            default => $query->orderByRaw('last_transaction_at IS NULL')->orderBy('last_transaction_at', 'desc')->orderBy('id', 'desc'),
        };

        $customers = $query->paginate($validated['per_page'] ?? 20)->withQueryString();

        return CustomerResource::collection($customers)->additional([
            'summary' => [
                'total_due' => number_format((float) Customer::where('current_balance', '>', 0)->sum('current_balance'), 2, '.', ''),
                'total_advance' => number_format(abs((float) Customer::where('current_balance', '<', 0)->sum('current_balance')), 2, '.', ''),
                'customer_count' => Customer::count(),
            ],
        ]);
    }

    /**
     * POST /api/v1/customers — add a customer (optional opening balance).
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->createCustomer($request->validated(), $request->user());

        return response()->json([
            'message' => "Grahak '{$customer->name}' jod diya gaya hai.",
            'data' => new CustomerResource($customer->fresh()),
        ], 201);
    }

    /**
     * GET /api/v1/customers/{customer} — customer details with totals.
     */
    public function show(Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($customer);

        return response()->json([
            'data' => new CustomerResource($customer),
            'totals' => [
                'total_udhaar' => $customer->total_credit,
                'total_jama' => $customer->total_payment,
            ],
        ]);
    }

    /**
     * PUT /api/v1/customers/{customer} — edit customer details.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($customer);
        $validated = $request->validated();

        $customer->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'credit_limit' => $validated['credit_limit'] ?? 0.00,
        ]);

        return response()->json([
            'message' => 'Customer details updated.',
            'data' => new CustomerResource($customer->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/customers/{customer} — archive (soft delete) a customer.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($customer);
        $this->customerService->archiveCustomer($customer);

        return response()->json(['message' => "Customer '{$customer->name}' has been archived."]);
    }

    /**
     * POST /api/v1/customers/{id}/restore — bring back an archived customer.
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        $customer = Customer::onlyTrashed()
            ->where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $customer->restore();
        $customer->status = 'active';
        $customer->save();

        return response()->json([
            'message' => "Customer '{$customer->name}' has been restored.",
            'data' => new CustomerResource($customer),
        ]);
    }

    /**
     * GET /api/v1/customers/{customer}/transactions — the customer's ledger (newest first).
     */
    public function transactions(Request $request, Customer $customer): AnonymousResourceCollection
    {
        $this->authorizeCustomer($customer);
        $perPage = min(50, max(1, (int) $request->input('per_page', 30)));

        return TransactionResource::collection(
            $customer->transactions()->with('user')->paginate($perPage)->withQueryString()
        );
    }

    protected function authorizeCustomer(Customer $customer): void
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            abort(404, 'Customer not found.');
        }
    }
}
