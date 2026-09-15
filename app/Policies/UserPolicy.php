<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the specific user profile.
     */
    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->id === $model->id;
    }

    /**
     * Determine whether the user can update the specific user profile.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->id === $model->id;
    }

    /**
     * Determine whether the user can update the user's role.
     */
    public function updateRole(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can block or unblock the user.
     */
    public function block(User $user, User $model): bool
    {
        // Only admin can block users, and admin cannot block themselves
        return $user->isAdmin() && $user->id !== $model->id;
    }
}
