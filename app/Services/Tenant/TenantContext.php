<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Company;
use Closure;

class TenantContext
{
    protected ?Company $company = null;
    protected bool $bypassScope = false;

    /**
     * Set active company tenant.
     */
    public function setCompany(?Company $company): void
    {
        $this->company = $company;
    }

    /**
     * Get active company tenant.
     */
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    /**
     * Get active company ID.
     */
    public function getCompanyId(): ?int
    {
        return $this->company?->id;
    }

    /**
     * Check if a tenant is currently set.
     */
    public function hasCompany(): bool
    {
        return $this->company !== null;
    }

    /**
     * Clear the current tenant context.
     */
    public function clear(): void
    {
        $this->company = null;
        $this->bypassScope = false;
    }

    /**
     * Check if the tenant global scope is bypassed.
     */
    public function isBypassed(): bool
    {
        return $this->bypassScope;
    }

    /**
     * Execute callback bypassing tenant scope (e.g. for super admin or system operations).
     */
    public function runWithoutScope(Closure $callback): mixed
    {
        $previous = $this->bypassScope;
        $this->bypassScope = true;

        try {
            return $callback();
        } finally {
            $this->bypassScope = $previous;
        }
    }
}

