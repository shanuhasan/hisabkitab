<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'duration_days',
        'customer_limit',
        'features',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function isFree(): bool
    {
        return (float) $this->price == 0.0;
    }

    public function hasFeature(string $feature): bool
    {
        return !empty($this->features[$feature]);
    }

    public function getCustomerLimitFormattedAttribute(): string
    {
        return $this->customer_limit === -1 ? 'Unlimited' : (string) $this->customer_limit;
    }
}
