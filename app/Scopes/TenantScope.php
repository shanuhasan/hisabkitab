<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * Enforces strict row-level tenant isolation across all tenant queries.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Super Admin can view all records when explicitly managing platform,
            // otherwise regular shop owners/staff are strictly isolated to their own tenant_id.
            if ($user->role !== 'super_admin' && !empty($user->tenant_id)) {
                $builder->where($model->getTable() . '.tenant_id', '=', $user->tenant_id);
            }
        }
    }
}

