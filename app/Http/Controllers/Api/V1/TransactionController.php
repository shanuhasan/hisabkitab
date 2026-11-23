<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReverseTransactionRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\TransactionResource;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\TransactionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    public function __construct(protected TransactionService $transactionService) {}

    /**
     * GET /api/v1/transactions — shop-wide entry register.
     *
     * Query: search, type (credit|payment|reversal), payment_mode, from_date, to_date, per_page (max 50)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:credit,payment,reversal'],
            'payment_mode' => ['nullable', 'in:cash,upi,bank_transfer,cheque,other'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Transaction::with(['customer', 'user'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        if ($search = trim($validated['search'] ?? '')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        foreach (['type', 'payment_mode'] as $field) {
            if (!empty($validated[$field])) {
                $query->where($field, $validated[$field]);
            }
        }
        if (!empty($validated['from_date'])) {
            $query->whereDate('transaction_date', '>=', $validated['from_date']);
        }
        if (!empty($validated['to_date'])) {
            $query->whereDate('transaction_date', '<=', $validated['to_date']);
        }

        $totalCredit = (clone $query)->where('type', 'credit')->where('is_reversed', false)->sum('amount');
        $totalPayment = (clone $query)->where('type', 'payment')->where('is_reversed', false)->sum('amount');

        return TransactionResource::collection(
            $query->paginate($validated['per_page'] ?? 20)->withQueryString()
        )->additional([
            'summary' => [
                'total_udhaar' => number_format((float) $totalCredit, 2, '.', ''),
                'total_jama' => number_format((float) $totalPayment, 2, '.', ''),
            ],
        ]);
    }

    /**
     * POST /api/v1/transactions — record Udhaar (type=credit) or Jama (type=payment).
     * Accepts multipart/form-data when a bill_image photo is attached.
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $customer = Customer::findOrFail($validated['customer_id']);

        if ($request->hasFile('bill_image')) {
            $validated['bill_image_path'] = $request->file('bill_image')->store('bills', 'public');
        }

        $txn = $this->transactionService->recordTransaction($customer, $request->user(), $validated);
        $label = $txn->type === 'credit' ? 'Udhaar' : 'Jama';

        return response()->json([
            'message' => "{$label} ₹{$txn->amount} saved. Naya balance: ₹" . number_format((float) $txn->running_balance, 2),
            'data' => new TransactionResource($txn->load(['customer', 'user'])),
            'customer' => new CustomerResource($customer->fresh()),
        ], 201);
    }

    /**
     * GET /api/v1/transactions/{transaction} — one entry's details.
     */
    public function show(Transaction $transaction): JsonResponse
    {
        $this->authorizeTransaction($transaction);

        $transaction->load(['customer', 'user', 'reversal']);

        return response()->json([
            'data' => new TransactionResource($transaction),
            'reversal_reason' => $transaction->reversal?->reason,
        ]);
    }

    /**
     * POST /api/v1/transactions/{transaction}/reverse — undo a wrong entry (shop owner only).
     */
    public function reverse(ReverseTransactionRequest $request, Transaction $transaction): JsonResponse
    {
        $this->authorizeTransaction($transaction);

        try {
            $reversal = $this->transactionService->reverseTransaction(
                $transaction,
                $request->user(),
                $request->validated('reason')
            );
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Entry {$transaction->transaction_number} reversed.",
            'data' => new TransactionResource($reversal->load(['customer', 'user'])),
            'customer' => new CustomerResource(Customer::find($transaction->customer_id)),
        ]);
    }

    protected function authorizeTransaction(Transaction $transaction): void
    {
        if ($transaction->tenant_id !== auth()->user()->tenant_id) {
            abort(404, 'Entry not found.');
        }
    }
}
