<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Customer
 */
class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $balance = (float) $this->current_balance;

        // Simple status the app can colour: due = customer owes the shop (red),
        // advance = shop holds customer's money (green), settled = zero (grey).
        $balanceStatus = $balance > 0 ? 'due' : ($balance < 0 ? 'advance' : 'settled');
        $absolute = number_format(abs($balance), 2, '.', '');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'notes' => $this->notes,
            'credit_limit' => (string) $this->credit_limit,
            'balance' => (string) $this->current_balance,
            'balance_status' => $balanceStatus,
            'balance_label' => match ($balanceStatus) {
                'due' => "Lena hai ₹{$absolute}",
                'advance' => "Advance ₹{$absolute}",
                default => 'Hisab barabar',
            },
            'is_archived' => $this->trashed(),
            'last_transaction_at' => $this->last_transaction_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
