<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\CompanyStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($user->status !== UserStatus::Active) {
            return response()->json([
                'message' => 'Your user account is suspended or inactive.',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($user->isSuperAdmin()) {
            $switchCompanyId = $request->header('X-Company-ID') ?? $request->query('company_id');

            if ($switchCompanyId !== null) {
                $company = Company::find($switchCompanyId);

                if ($company === null) {
                    return response()->json([
                        'message' => 'Requested company not found.',
                    ], Response::HTTP_NOT_FOUND);
                }

                $this->tenantContext->setCompany($company);
            }

            return $next($request);
        }

        if ($user->company_id === null) {
            return response()->json([
                'message' => 'User is not assigned to any company.',
            ], Response::HTTP_FORBIDDEN);
        }

        $company = $user->company;

        if ($company === null || $company->status !== CompanyStatus::Active) {
            return response()->json([
                'message' => 'Company account is inactive or suspended.',
            ], Response::HTTP_FORBIDDEN);
        }

        $this->tenantContext->setCompany($company);

        return $next($request);
    }
}

