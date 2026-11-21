<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Subscription extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'grace_ends_at',
        'status', // active, trial, grace_period, expired, cancelled
        'payment_details',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'trial_ends_at' => 'date',
            'grace_ends_at' => 'date',
            'payment_details' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /**
     * Check if subscription is validly active (active, active trial, or within grace period).
     */
    public function isValid(): bool
    {
        return $this->isActive() || $this->isInTrial() || $this->isInGracePeriod();
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $this->ends_at === null || $this->ends_at->isFuture() || $this->ends_at->isToday();
    }

    public function isInTrial(): bool
    {
        if ($this->status === 'trial') {
            return $this->trial_ends_at === null || $this->trial_ends_at->isFuture() || $this->trial_ends_at->isToday();
        }

        return false;
    }

    public function isInGracePeriod(): bool
    {
        if ($this->status === 'grace_period') {
            return $this->grace_ends_at === null || $this->grace_ends_at->isFuture() || $this->grace_ends_at->isToday();
        }

        // Auto-check if ended within 3 days grace period
        if ($this->ends_at && $this->ends_at->isPast()) {
            $graceDeadline = $this->ends_at->copy()->addDays(3);
            return Carbon::today()->lte($graceDeadline);
        }

        return false;
    }

    public function isExpired(): bool
    {
        return !$this->isValid();
    }

    public function daysRemaining(): int
    {
        $targetDate = $this->ends_at ?? $this->trial_ends_at;
        if (!$targetDate) {
            return 999;
        }

        $diff = Carbon::today()->diffInDays($targetDate, false);
        return max(0, (int) $diff);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'active' => 'bg-success',
            'trial' => 'bg-primary',
            'grace_period' => 'bg-warning text-dark',
            'expired' => 'bg-danger',
            'cancelled' => 'bg-secondary',
            default => 'bg-dark',
        };
    }
}
