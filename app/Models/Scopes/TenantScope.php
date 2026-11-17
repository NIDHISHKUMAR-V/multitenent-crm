<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Services\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder<Model> $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantContext = app(TenantContext::class);

        if ($tenantContext->isBypassed()) {
            return;
        }

        $companyId = $tenantContext->getCompanyId();

        if ($companyId === null && auth()->check()) {
            $user = auth()->user();

            if ($user->isSuperAdmin()) {
                return;
            }

            $companyId = $user->company_id;
        }

        if ($companyId !== null) {
            $builder->where($model->qualifyColumn('company_id'), $companyId);
        }
    }
}

