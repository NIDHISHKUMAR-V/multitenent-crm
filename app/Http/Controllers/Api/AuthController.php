<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthenticationService;
use App\Services\Auth\CompanyRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Register a new company and its initial administrator.
     */
    public function registerCompany(
        RegisterCompanyRequest $request,
        CompanyRegistrationService $registrationService
    ): JsonResponse {
        $result = $registrationService->register($request->validated());

        return response()->json([
            'message' => 'Company and administrator registered successfully.',
            'company' => new CompanyResource($result['company']),
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], Response::HTTP_CREATED);
    }

    /**
     * Authenticate a user and return an API token.
     */
    public function login(
        LoginRequest $request,
        AuthenticationService $authenticationService
    ): JsonResponse {
        $result = $authenticationService->authenticate($request->validated());

        return response()->json([
            'message' => 'Logged in successfully.',
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    /**
     * Log out the authenticated user by revoking the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Get the authenticated user's profile and company information.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('company');

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }
}

