<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Events\UserRegistered;
use App\Exceptions\AccountBlockedException;
use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Зарегистрировать нового пользователя в системе.
     *
     * @param  array{name: string, email: string, password: string}  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::Student,
            'is_blocked' => false,
        ]);

        event(new UserRegistered($user));

        $token = $user->createToken('auth_token');

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Аутентифицировать пользователя по учетным данным и выдать токен доступа.
     *
     * @return array{user: User, token: string}
     *
     * @throws InvalidCredentialsException
     * @throws AccountBlockedException
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new InvalidCredentialsException;
        }

        if ($user->isBlocked()) {
            throw new AccountBlockedException;
        }

        $token = $user->createToken('auth_token');

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Отозвать текущий токен доступа пользователя.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
