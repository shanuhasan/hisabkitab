<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\TransactionReversal;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService
{
    public function __construct(protected BalanceService $balanceService) {}

    /**
     * Generate unique transaction reference number per tenant.
     */
    public function generateTransactionNumber(int $tenantId): string
    {
        return sprintf('TXN-%s-%05d', date('Ym'), rand(10000, 99999));
    }

    /**
     * Record a new credit (Udhaar) or payment (Jama) transaction atomically.
     */
    public function recordTransaction(Customer $customer, User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($customer, $user, $data) {
            // Lock customer record for update to prevent concurrent race conditions
            $lockedCustomer = Customer::lockForUpdate()->findOrFail($customer->id);

            $amount = number_format((float) $data['amount'], 2, '.', '');
            $type = $data['type']; // 'credit' or 'payment'

            if ($type === 'credit') {
                $newBalance = bcadd((string) $lockedCustomer->current_balance, $amount, 2);
            } else {
                $newBalance = bcsub((string) $lockedCustomer->current_balance, $amount, 2);
            }

            $txn = Transaction::create([
                'tenant_id' => $user->tenant_id,
                'customer_id' => $lockedCustomer->id,
                'user_id' => $user->id,
                'transaction_number' => $this->generateTransactionNumber($user->tenant_id),
                'type' => $type,
                'amount' => $amount,
                'running_balance' => $newBalance,
                'payment_mode' => $data['payment_mode'] ?? 'cash',
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'description' => $data['description'] ?? null,
                'bill_image_path' => $data['bill_image_path'] ?? null,
                'is_reversed' => false,
            ]);

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

            $customer = Customer::lockForUpdate()->findOrFail($originalTxn->customer_id);

            $amount = (string) $originalTxn->amount;

            // If original was credit (which increased balance), reversal decreases balance
            // If original was payment (which decreased balance), reversal increases balance
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
                'type' => 'reversal',
                'amount' => $amount,
                'running_balance' => $newBalance,
                'payment_mode' => $originalTxn->payment_mode,
                'transaction_date' => now()->toDateString(),
                'description' => 'Reversal of ' . $originalTxn->transaction_number . ': ' . $reason,
                'bill_image_path' => null,
                'is_reversed' => false,
            ]);

            // Flag original transaction as reversed
            $originalTxn->is_reversed = true;
            $originalTxn->save();

            // Record audit link
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

