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
     * The current authenticated access token instance.
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
     * Get all API tokens for the user.
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    /**
     * Create a new API token for the user.
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
     * Set the current access token.
     */
    public function withAccessToken(ApiToken $token): self
    {
        $this->currentAccessToken = $token;

        return $this;
    }

    /**
     * Get the current access token.
     */
    public function currentAccessToken(): ?ApiToken
    {
        return $this->currentAccessToken;
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Check if the user is a teacher.
     */
    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    /**
     * Check if the user is a student.
     */
    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    /**
     * Check if user has given role.
     */
    public function hasRole(UserRole|string $role): bool
    {
        $roleValue = $role instanceof UserRole ? $role->value : $role;

        return $this->role?->value === $roleValue;
    }

    /**
     * Check if user is blocked.
     */
    public function isBlocked(): bool
    {
        return (bool) $this->is_blocked;
    }
}
