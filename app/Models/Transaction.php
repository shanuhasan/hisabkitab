<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'user_id',
        'transaction_number',
        'reference_number',
        'type',
        'amount',
        'running_balance',
        'payment_mode',
        'transaction_date',
        'description',
        'bill_image_path',
        'is_reversed',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'running_balance' => 'decimal:2',
            'transaction_date' => 'date',
            'is_reversed' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(TransactionReversal::class, 'original_transaction_id');
    }
}

