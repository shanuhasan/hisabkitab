<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\TransactionReversal;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function __construct(protected BalanceService $balanceService) {}

    /**
     * Generate unique sequential transaction reference number per tenant.
     */
    public function generateTransactionNumber(int $tenantId): string
    {
        $count = Transaction::where('tenant_id', $tenantId)->count() + 1;
        return sprintf('TXN-%s-%05d', date('Ym'), $count);
    }

    /**
     * Record a new credit (Udhaar) or payment (Jama) transaction atomically.
     * Enforces pessimistic locking, bcmath arithmetic, and balance validation.
     */
    public function recordTransaction(Customer $customer, User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($customer, $user, $data) {
            // Lock customer record for update to prevent concurrent race conditions
            $lockedCustomer = Customer::lockForUpdate()->findOrFail($customer->id);

            $amount = number_format((float) $data['amount'], 2, '.', '');
            $type = $data['type']; // 'credit' or 'payment'
            $allowAdvance = !empty($data['allow_advance']);

            // Security & Integrity: Prevent accidental duplicate submissions (e.g. double clicks within 3 seconds)
            $recentDuplicate = Transaction::where('tenant_id', $user->tenant_id)
                ->where('customer_id', $lockedCustomer->id)
                ->where('type', $type)
                ->where('amount', $amount)
                ->where('created_at', '>=', now()->subSeconds(3))
                ->exists();

            if ($recentDuplicate) {
                throw ValidationException::withMessages([
                    'amount' => 'A duplicate transaction with the exact same amount was submitted just now. Please wait a moment before trying again.',
                ]);
            }

            // Financial Rule: Validate payment amount against outstanding balance
            if ($type === 'payment' && !$allowAdvance) {
                // If customer balance is <= 0 or payment exceeds balance
                if (bccomp((string) $lockedCustomer->current_balance, '0.00', 2) <= 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'Customer has no outstanding balance. Check "Allow advance payment" if the customer is depositing advance money.',
                    ]);
                }

                if (bccomp($amount, (string) $lockedCustomer->current_balance, 2) === 1) {
                    $formattedBalance = number_format((float) $lockedCustomer->current_balance, 2);
                    throw ValidationException::withMessages([
                        'amount' => "Payment amount (₹ {$amount}) exceeds the customer's current outstanding balance (₹ {$formattedBalance}). Check 'Allow advance payment' to accept excess advance.",
                    ]);
                }
            }

            // High-precision monetary arithmetic (bcmath)
            if ($type === 'credit') {
                $newBalance = bcadd((string) $lockedCustomer->current_balance, $amount, 2);
            } else {
                $newBalance = bcsub((string) $lockedCustomer->current_balance, $amount, 2);
            }

            // Create immutable transaction entry
            $txn = Transaction::create([
                'tenant_id' => $user->tenant_id,
                'customer_id' => $lockedCustomer->id,
                'user_id' => $user->id,
                'transaction_number' => $this->generateTransactionNumber($user->tenant_id),
                'reference_number' => !empty($data['reference_number']) ? trim($data['reference_number']) : null,
                'type' => $type,
                'amount' => $amount,
                'running_balance' => $newBalance,
                'payment_mode' => $data['payment_mode'] ?? 'cash',
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'description' => $data['description'] ?? null,
                'bill_image_path' => $data['bill_image_path'] ?? null,
                'is_reversed' => false,
            ]);

            // Update cached balance on customer
            $lockedCustomer->current_balance = $newBalance;
            $lockedCustomer->last_transaction_at = now();
            $lockedCustomer->save();

            return $txn;
        });
    }

    /**
     * Reverse an erroneous transaction with an audited reversal entry.
     */
    public function reverseTransaction(Transaction $originalTxn, User $user, string $reason): Transaction
    {
        return DB::transaction(function () use ($originalTxn, $user, $reason) {
            if ($originalTxn->is_reversed) {
                throw new Exception('This transaction has already been reversed.');
            }

            if ($originalTxn->type === 'reversal') {
                throw new Exception('Reversal transactions cannot be reversed.');
            }

            $customer = Customer::lockForUpdate()->findOrFail($originalTxn->customer_id);

            $amount = (string) $originalTxn->amount;

            // Reversal arithmetic:
            // If original was credit (+), reversal subtracts (-)
            // If original was payment (-), reversal adds (+)
            if ($originalTxn->type === 'credit') {
                $newBalance = bcsub((string) $customer->current_balance, $amount, 2);
            } else {
                $newBalance = bcadd((string) $customer->current_balance, $amount, 2);
            }

            // Create reversal transaction
            $reversalTxn = Transaction::create([
                'tenant_id' => $user->tenant_id,
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'transaction_number' => $this->generateTransactionNumber($user->tenant_id),
                'reference_number' => 'REV-' . $originalTxn->transaction_number,
                'type' => 'reversal',
                'amount' => $amount,
                'running_balance' => $newBalance,
                'payment_mode' => $originalTxn->payment_mode,
                'transaction_date' => now()->toDateString(),
                'description' => 'Reversal of ' . $originalTxn->transaction_number . ': ' . $reason,
                'bill_image_path' => null,
                'is_reversed' => false,
            ]);

            // Mark original transaction as reversed
            $originalTxn->is_reversed = true;
            $originalTxn->save();

            // Record audit log
            TransactionReversal::create([
                'tenant_id' => $user->tenant_id,
                'original_transaction_id' => $originalTxn->id,
                'reversal_transaction_id' => $reversalTxn->id,
                'user_id' => $user->id,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            // Update customer cached balance
            $customer->current_balance = $newBalance;
            $customer->save();

            return $reversalTxn;
        });
    }
}
