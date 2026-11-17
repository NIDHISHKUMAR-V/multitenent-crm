<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Company;
use App\Models\Scopes\TenantScope;
use App\Services\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    /**
     * Boot the tenant trait for a model.
     */
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model): void {
            if (empty($model->company_id)) {
                $tenantContext = app(TenantContext::class);
                $companyId = $tenantContext->getCompanyId() ?? auth()->user()?->company_id;

                if ($companyId !== null) {
                    $model->company_id = $companyId;
                }
            }
        });
    }

    /**
     * Relationship to the company.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

