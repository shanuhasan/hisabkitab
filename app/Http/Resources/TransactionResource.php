<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \App\Models\Transaction
 */
class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->transaction_number,
            'type' => $this->type,
            'type_label' => match ($this->type) {
                'credit' => 'Udhaar Diya',
                'payment' => 'Jama Mila',
                default => 'Reversal',
            },
            'amount' => (string) $this->amount,
            'balance_after' => (string) $this->running_balance,
            'payment_mode' => $this->payment_mode,
            'date' => $this->transaction_date?->toDateString(),
            'description' => $this->description,
            'reference_number' => $this->reference_number,
            'bill_image_url' => $this->bill_image_path ? Storage::disk('public')->url($this->bill_image_path) : null,
            'is_reversed' => (bool) $this->is_reversed,
            'can_reverse' => !$this->is_reversed && $this->type !== 'reversal',
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
            ] : null),
            'recorded_by' => $this->whenLoaded('user', fn () => $this->user?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
