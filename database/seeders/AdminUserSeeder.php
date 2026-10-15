<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(RbacSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password123'),
                'is_blocked' => false,
            ]
        );
        $admin->syncRoles([UserRole::Admin->value]);

        $teacher = User::firstOrCreate(
            ['email' => 'teacher@example.com'],
            [
                'name' => 'John Teacher',
                'password' => Hash::make('password123'),
                'is_blocked' => false,
            ]
        );
        $teacher->syncRoles([UserRole::Teacher->value]);

        $student = User::firstOrCreate(
            ['email' => 'student@example.com'],
            [
                'name' => 'Jane Student',
                'password' => Hash::make('password123'),
                'is_blocked' => false,
            ]
        );
        $student->syncRoles([UserRole::Student->value]);
    }
}
