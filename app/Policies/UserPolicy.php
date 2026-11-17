<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $authenticatedUser): bool
    {
        return $authenticatedUser->isSuperAdmin()
            || $authenticatedUser->isCompanyAdmin()
            || $authenticatedUser->isManager();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $authenticatedUser, User $targetUser): bool
    {
        if ($authenticatedUser->isSuperAdmin()) {
            return true;
        }

        return $authenticatedUser->company_id === $targetUser->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $authenticatedUser): bool
    {
        return $authenticatedUser->canManageCompany();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $authenticatedUser, User $targetUser): bool
    {
        if ($authenticatedUser->isSuperAdmin()) {
            return true;
        }

        if ($authenticatedUser->isCompanyAdmin()) {
            return $authenticatedUser->company_id === $targetUser->company_id;
        }

        return $authenticatedUser->id === $targetUser->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $authenticatedUser, User $targetUser): bool
    {
        if ($authenticatedUser->isSuperAdmin()) {
            return $authenticatedUser->id !== $targetUser->id;
        }

        if ($authenticatedUser->isCompanyAdmin()) {
            return $authenticatedUser->company_id === $targetUser->company_id
                && $authenticatedUser->id !== $targetUser->id;
        }

        return false;
    }
}

