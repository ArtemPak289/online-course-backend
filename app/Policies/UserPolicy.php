<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Определить, может ли пользователь просматривать список пользователей.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Определить, может ли пользователь просматривать профиль конкретного пользователя.
     */
    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->id === $model->id;
    }

    /**
     * Определить, может ли пользователь обновлять профиль конкретного пользователя.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->id === $model->id;
    }

    /**
     * Определить, может ли пользователь изменять роль пользователя.
     */
    public function updateRole(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Определить, может ли пользователь блокировать или разблокировать пользователя.
     */
    public function block(User $user, User $model): bool
    {
        // Только администратор может блокировать пользователей, и администратор не может заблокировать сам себя
        return $user->isAdmin() && $user->id !== $model->id;
    }
}
