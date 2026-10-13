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
        return $user->hasPermission('users.view');
    }

    /**
     * Определить, может ли пользователь просматривать профиль конкретного пользователя.
     */
    public function view(User $user, User $model): bool
    {
        return $user->hasPermission('users.view') || $user->id === $model->id;
    }

    /**
     * Определить, может ли пользователь обновлять профиль конкретного пользователя.
     */
    public function update(User $user, User $model): bool
    {
        return $user->hasPermission('users.manage') || $user->id === $model->id;
    }

    /**
     * Определить, может ли пользователь изменять роль пользователя.
     */
    public function updateRole(User $user, User $model): bool
    {
        return $user->hasPermission('users.manage');
    }

    /**
     * Определить, может ли пользователь блокировать или разблокировать пользователя.
     */
    public function block(User $user, User $model): bool
    {
        // Только пользователь с правом управления пользователями может блокировать пользователей, но не самого себя
        return $user->hasPermission('users.manage') && $user->id !== $model->id;
    }
}
