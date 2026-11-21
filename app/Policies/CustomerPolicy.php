<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Determine whether the user can view any customers.
     */
    public function viewAny(User $user): bool
    {
        return !empty($user->tenant_id) || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the specific customer.
     */
    public function view(User $user, Customer $customer): bool
    {
        return $user->isSuperAdmin() || ($user->tenant_id === $customer->tenant_id);
    }

    /**
     * Determine whether the user can create customers.
     */
    public function create(User $user): bool
    {
        return !empty($user->tenant_id);
    }

    /**
     * Determine whether the user can update the customer.
     */
    public function update(User $user, Customer $customer): bool
    {
        return $user->tenant_id === $customer->tenant_id;
    }

    /**
     * Determine whether the user can delete the customer.
     * Only shop owners can delete customers with zero balance.
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $user->isShopOwner() && ($user->tenant_id === $customer->tenant_id) && ((float) $customer->current_balance === 0.0);
    }
}

