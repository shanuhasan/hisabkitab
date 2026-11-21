<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    /**
     * Determine whether the user can view transactions.
     */
    public function viewAny(User $user): bool
    {
        return !empty($user->tenant_id) || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the specific transaction.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        return $user->isSuperAdmin() || ($user->tenant_id === $transaction->tenant_id);
    }

    /**
     * Determine whether the user can create transactions.
     */
    public function create(User $user): bool
    {
        return !empty($user->tenant_id);
    }

    /**
     * Determine whether the user can reverse the transaction.
     * Only shop owners can reverse transactions within their own tenant.
     */
    public function reverse(User $user, Transaction $transaction): bool
    {
        return $user->isShopOwner() && ($user->tenant_id === $transaction->tenant_id) && !$transaction->is_reversed;
    }
}

