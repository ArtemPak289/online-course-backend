<?php

namespace App\Services;

use App\Models\User;

class ProfileService
{
    /**
     * Обновить данные профиля пользователя.
     *
     * @param  array{name?: string, email?: string}  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    /**
     * Изменить пароль пользователя.
     */
    public function changePassword(User $user, string $newPassword): void
    {
        $user->update([
            'password' => $newPassword,
        ]);
    }
}
