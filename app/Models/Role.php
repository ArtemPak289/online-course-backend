<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Получить пользователей, которым назначена данная роль.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Получить права доступа, привязанные к данной роли.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * Назначить право доступа данной роли.
     */
    public function givePermissionTo(Permission|string $permission): self
    {
        $permissionModel = is_string($permission)
            ? Permission::firstOrCreate(['name' => $permission])
            : $permission;

        $this->permissions()->syncWithoutDetaching([$permissionModel->id]);

        return $this;
    }

    /**
     * Отозвать право доступа у данной роли.
     */
    public function revokePermissionTo(Permission|string $permission): self
    {
        $permissionModel = is_string($permission)
            ? Permission::where('name', $permission)->first()
            : $permission;

        if ($permissionModel) {
            $this->permissions()->detach($permissionModel->id);
        }

        return $this;
    }

    /**
     * Проверить, содержит ли роль указанное право доступа.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions->contains('name', $permission);
    }
}
