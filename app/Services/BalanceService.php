<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    /**
     * Recalculate and reconcile a customer's running balance from all transactions.
     * Uses bcmath to prevent IEEE 754 floating point imprecision.
     */
    public function recalculateCustomerBalance(int $customerId): string
    {
        return DB::transaction(function () use ($customerId) {
            $customer = Customer::lockForUpdate()->findOrFail($customerId);

            $transactions = Transaction::where('customer_id', $customer->id)
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $running = '0.00';

            foreach ($transactions as $txn) {
                if ($txn->type === 'credit') {
                    // Udhaar given increases outstanding balance
                    $running = bcadd($running, (string) $txn->amount, 2);
                } elseif ($txn->type === 'payment') {
                    // Payment received decreases outstanding balance
                    $running = bcsub($running, (string) $txn->amount, 2);
                } elseif ($txn->type === 'reversal') {
                    // Check reversed transaction original type
                    // If reversal was for credit, it subtracts; if for payment, it adds
                    $reversal = $txn->reversal;
                    if ($reversal && $reversal->originalTransaction) {
                        if ($reversal->originalTransaction->type === 'credit') {
                            $running = bcsub($running, (string) $txn->amount, 2);
                        } else {
                            $running = bcadd($running, (string) $txn->amount, 2);
                        }
                    }
                }

                if ($txn->running_balance !== $running) {
                    $txn->running_balance = $running;
                    $txn->saveQuietly();
                }
            }

            $customer->current_balance = $running;
            $customer->save();

            return $running;
        });
    }
}

