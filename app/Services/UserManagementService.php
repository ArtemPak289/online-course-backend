<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserManagementService
{
    /**
     * Получить список пользователей с пагинацией и фильтрами.
     *
     * @param  array{role?: string, is_blocked?: bool|string}  $filters
     */
    public function getPaginatedUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::with('roles');

        if (! empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        if (isset($filters['is_blocked']) && $filters['is_blocked'] !== '') {
            $query->where('is_blocked', filter_var($filters['is_blocked'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Обновить роль пользователя.
     */
    public function updateRole(User $user, UserRole|string $role): User
    {
        $user->syncRoles([$role]);

        return $user->fresh(['roles']);
    }

    /**
     * Обновить статус блокировки пользователя.
     */
    public function updateBlockStatus(User $user, bool $isBlocked): User
    {
        $user->update([
            'is_blocked' => $isBlocked,
        ]);

        if ($isBlocked) {
            // Отозвать все активные токены при блокировке пользователя
            $user->tokens()->delete();
        }

        return $user->fresh(['roles']);
    }
}
