<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $userRoleValue = $user->role?->value;

        if ($userRoleValue === null || !in_array($userRoleValue, $roles, true)) {
            return response()->json([
                'message' => 'Forbidden: You do not have permission to access this resource.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

