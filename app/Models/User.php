<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
     * Дополнительные атрибуты, добавляемые в сериализацию массива / JSON.
     *
     * @var list<string>
     */
    protected $appends = [
        'role',
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
            'is_blocked' => 'boolean',
        ];
    }

    /**
     * Получить роли, назначенные пользователю.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Получить курсы, созданные данным пользователем (преподавателем).
     */
    public function authoredCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'author_id');
    }

    /**
     * Аксессор для получения основной роли пользователя (совместимость с кодом и тестами).
     */
    public function getRoleAttribute(): ?UserRole
    {
        $roleName = $this->roles->first()?->name;

        return $roleName ? UserRole::tryFrom($roleName) : null;
    }

    /**
     * Назначить роль пользователю.
     */
    public function assignRole(Role|UserRole|string $role): self
    {
        $roleName = $role instanceof UserRole ? $role->value : ($role instanceof Role ? $role->name : $role);
        $roleModel = Role::firstOrCreate(['name' => $roleName]);

        $this->roles()->syncWithoutDetaching([$roleModel->id]);
        $this->unsetRelation('roles');

        return $this;
    }

    /**
     * Отозвать роль у пользователя.
     */
    public function removeRole(Role|UserRole|string $role): self
    {
        $roleName = $role instanceof UserRole ? $role->value : ($role instanceof Role ? $role->name : $role);
        $roleModel = Role::where('name', $roleName)->first();

        if ($roleModel) {
            $this->roles()->detach($roleModel->id);
            $this->unsetRelation('roles');
        }

        return $this;
    }

    /**
     * Синхронизировать роли пользователя.
     *
     * @param  array<Role|UserRole|string>  $roles
     */
    public function syncRoles(array $roles): self
    {
        $roleIds = [];
        foreach ($roles as $role) {
            $roleName = $role instanceof UserRole ? $role->value : ($role instanceof Role ? $role->name : $role);
            $roleModel = Role::firstOrCreate(['name' => $roleName]);
            $roleIds[] = $roleModel->id;
        }

        $this->roles()->sync($roleIds);
        $this->unsetRelation('roles');

        return $this;
    }

    /**
     * Проверить, имеет ли пользователь указанную роль (или одну из указанных ролей).
     *
     * @param  Role|UserRole|string|array<Role|UserRole|string>  $role
     */
    public function hasRole(Role|UserRole|string|array $role): bool
    {
        $roleList = is_array($role) ? $role : [$role];
        $expectedNames = array_map(function ($r) {
            if ($r instanceof UserRole) {
                return $r->value;
            }
            if ($r instanceof Role) {
                return $r->name;
            }

            return (string) $r;
        }, $roleList);

        return $this->roles->contains(fn (Role $r) => in_array($r->name, $expectedNames, true));
    }

    /**
     * Проверить, имеет ли пользователь указанное право доступа.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $this->loadMissing('roles.permissions');

        return $this->roles->flatMap(fn (Role $r) => $r->permissions)->contains('name', $permission);
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
        return $this->hasRole(UserRole::Admin);
    }

    /**
     * Проверить, является ли пользователь преподавателем.
     */
    public function isTeacher(): bool
    {
        return $this->hasRole(UserRole::Teacher);
    }

    /**
     * Проверить, является ли пользователь студентом.
     */
    public function isStudent(): bool
    {
        return $this->hasRole(UserRole::Student);
    }

    /**
     * Проверить, заблокирован ли пользователь.
     */
    public function isBlocked(): bool
    {
        return (bool) $this->is_blocked;
    }
}
