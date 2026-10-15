<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Определить, может ли пользователь просматривать список категорий.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Определить, может ли пользователь просматривать конкретную категорию.
     */
    public function view(?User $user, Category $category): bool
    {
        return true;
    }

    /**
     * Определить, может ли пользователь создавать категории.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Определить, может ли пользователь обновлять категорию.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    /**
     * Определить, может ли пользователь удалять категорию.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }
}
