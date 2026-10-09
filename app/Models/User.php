<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Экземпляр текущего аутентифицированного токена доступа.
     */
    protected ?ApiToken $currentAccessToken = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_blocked',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_blocked' => 'boolean',
        ];
    }

    /**
     * Получить все API-токены пользователя.
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    /**
     * Создать новый API-токен для пользователя.
     */
    public function createToken(string $name = 'auth_token', ?\DateTimeInterface $expiresAt = null): string
    {
        $plainTextToken = Str::random(64);

        $this->tokens()->create([
            'name' => $name,
            'token_hash' => hash('sha256', $plainTextToken),
            'expires_at' => $expiresAt,
        ]);

        return $plainTextToken;
    }

    /**
     * Установить текущий токен доступа.
     */
    public function withAccessToken(ApiToken $token): self
    {
        $this->currentAccessToken = $token;

        return $this;
    }

    /**
     * Получить текущий токен доступа.
     */
    public function currentAccessToken(): ?ApiToken
    {
        return $this->currentAccessToken;
    }

    /**
     * Проверить, является ли пользователь администратором.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Проверить, является ли пользователь преподавателем.
     */
    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    /**
     * Проверить, является ли пользователь студентом.
     */
    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    /**
     * Проверить, имеет ли пользователь указанную роль.
     */
    public function hasRole(UserRole|string $role): bool
    {
        $roleValue = $role instanceof UserRole ? $role->value : $role;

        return $this->role?->value === $roleValue;
    }

    /**
     * Проверить, заблокирован ли пользователь.
     */
    public function isBlocked(): bool
    {
        return (bool) $this->is_blocked;
    }
}
