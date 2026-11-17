<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {
    }

    /**
     * Display a listing of users in the company.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $currentUser = $request->user();

        $query = User::query()->with('company');

        if (!$currentUser->isSuperAdmin()) {
            $query->where('company_id', $this->tenantContext->getCompanyId() ?? $currentUser->company_id);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $users = $query->latest()->paginate(15);

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        $currentUser = $request->user();
        $companyId = $this->tenantContext->getCompanyId() ?? $currentUser->company_id;

        $validated = $request->validated();
        $validated['company_id'] = $companyId;
        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = $validated['status'] ?? UserStatus::Active->value;

        $user = User::create($validated);

        return response()->json([
            'message' => 'User created successfully.',
            'user' => new UserResource($user),
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        return response()->json([
            'user' => new UserResource($user->loadMissing('company')),
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        $validated = $request->validated();

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => new UserResource($user->fresh(['company'])),
        ]);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}

