<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_blocked' => false,
        ];
    }

    /**
     * Настроить фабрику модели.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (User $user) {
            if (! $user->relationLoaded('roles') || $user->roles->isEmpty()) {
                $user->setRelation('roles', collect([new Role(['name' => UserRole::Student->value])]));
            }
        })->afterCreating(function (User $user) {
            if ($user->roles()->doesntExist()) {
                $user->assignRole(UserRole::Student->value);
            }
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Указать, что пользователь является администратором.
     */
    public function admin(): static
    {
        return $this->afterMaking(function (User $user) {
            $user->setRelation('roles', collect([new Role(['name' => UserRole::Admin->value])]));
        })->afterCreating(function (User $user) {
            $user->syncRoles([UserRole::Admin->value]);
        });
    }

    /**
     * Указать, что пользователь является преподавателем.
     */
    public function teacher(): static
    {
        return $this->afterMaking(function (User $user) {
            $user->setRelation('roles', collect([new Role(['name' => UserRole::Teacher->value])]));
        })->afterCreating(function (User $user) {
            $user->syncRoles([UserRole::Teacher->value]);
        });
    }

    /**
     * Указать, что пользователь является студентом.
     */
    public function student(): static
    {
        return $this->afterMaking(function (User $user) {
            $user->setRelation('roles', collect([new Role(['name' => UserRole::Student->value])]));
        })->afterCreating(function (User $user) {
            $user->syncRoles([UserRole::Student->value]);
        });
    }

    /**
     * Указать, что пользователь заблокирован.
     */
    public function blocked(bool $blocked = true): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blocked' => $blocked,
        ]);
    }
}
