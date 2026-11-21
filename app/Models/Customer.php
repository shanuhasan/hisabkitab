<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'phone',
        'email',
        'address',
        'credit_limit',
        'current_balance',
        'status',
        'notes',
        'last_transaction_at',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'last_transaction_at' => 'datetime',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->orderBy('transaction_date', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Compute total credit given (Udhaar) from ledger entries.
     */
    public function getTotalCreditAttribute(): string
    {
        $sum = $this->transactions()->where('type', 'credit')->sum('amount');
        return number_format((float) $sum, 2, '.', '');
    }

    /**
     * Compute total payments received (Jama) from ledger entries.
     */
    public function getTotalPaymentAttribute(): string
    {
        $sum = $this->transactions()->where('type', 'payment')->sum('amount');
        return number_format((float) $sum, 2, '.', '');
    }

    /**
     * Check if customer is archived.
     */
    public function isArchived(): bool
    {
        return $this->trashed() || $this->status === 'inactive';
    }
}
