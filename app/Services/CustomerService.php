<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function __construct(
        protected TransactionService $transactionService,
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Throw a validation error if the shop's plan has no room for another customer.
     */
    public function ensureCanAddCustomer(User $user): void
    {
        $tenant = $user->tenant;

        if ($tenant && !$this->subscriptionService->canAddCustomer($tenant)) {
            $status = $this->subscriptionService->getSubscriptionStatus($tenant);
            throw ValidationException::withMessages([
                'name' => "Aapke {$status['plan']->name} ki customer limit ({$status['customer_limit']} grahak) poori ho chuki hai. Naye grahak jodne ke liye kripya apna plan upgrade karein.",
            ]);
        }
    }

    /**
     * Create a customer, optionally with an audited opening balance ledger entry.
     * Used by both the web app and the mobile API.
     */
    public function createCustomer(array $validated, User $user): Customer
    {
        $this->ensureCanAddCustomer($user);

        return DB::transaction(function () use ($validated, $user) {
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

            // 1. Create Customer record
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
    }

    /**
     * Archive a customer (soft delete + inactive status).
     */
    public function archiveCustomer(Customer $customer): void
    {
        $customer->status = 'inactive';
        $customer->save();
        $customer->delete();
    }
}
