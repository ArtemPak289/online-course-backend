<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_assigned_and_checked_for_role(): void
    {
        $user = User::factory()->create();

        $user->assignRole(UserRole::Teacher);

        $this->assertTrue($user->hasRole(UserRole::Teacher));
        $this->assertTrue($user->hasRole('teacher'));
        $this->assertTrue($user->isTeacher());
        $this->assertFalse($user->isAdmin());
    }

    public function test_user_can_have_multiple_roles(): void
    {
        $user = User::factory()->create();

        $user->assignRole(UserRole::Teacher);
        $user->assignRole(UserRole::Student);

        $this->assertTrue($user->hasRole(['teacher', 'admin']));
        $this->assertTrue($user->hasRole('student'));
        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_user_role_can_be_removed(): void
    {
        $user = User::factory()->teacher()->create();

        $this->assertTrue($user->isTeacher());

        $user->removeRole(UserRole::Teacher);

        $this->assertFalse($user->isTeacher());
    }

    public function test_user_roles_can_be_synced(): void
    {
        $user = User::factory()->student()->create();

        $user->syncRoles([UserRole::Teacher->value, UserRole::Admin->value]);

        $this->assertTrue($user->isTeacher());
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isStudent());
    }

    public function test_role_can_be_given_and_revoked_permissions(): void
    {
        $role = Role::create(['name' => 'manager']);
        $permission = Permission::create(['name' => 'reports.view']);

        $role->givePermissionTo($permission);
        $this->assertTrue($role->hasPermission('reports.view'));

        $role->revokePermissionTo($permission);
        $role->refresh();
        $this->assertFalse($role->hasPermission('reports.view'));
    }

    public function test_user_inherits_permission_from_role(): void
    {
        $role = Role::create(['name' => 'editor']);
        $role->givePermissionTo('articles.edit');

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue($user->hasPermission('articles.edit'));
        $this->assertFalse($user->hasPermission('articles.delete'));
    }

    public function test_admin_has_all_permissions_automatically(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->hasPermission('any.arbitrary.permission'));
        $this->assertTrue($admin->hasPermission('users.manage'));
    }

    public function test_rbac_seeder_populates_roles_and_permissions(): void
    {
        $this->seed(RbacSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'teacher']);
        $this->assertDatabaseHas('roles', ['name' => 'student']);

        $this->assertDatabaseHas('permissions', ['name' => 'users.view']);
        $this->assertDatabaseHas('permissions', ['name' => 'courses.create']);
        $this->assertDatabaseHas('permissions', ['name' => 'lessons.complete']);

        $teacherRole = Role::where('name', 'teacher')->first();
        $this->assertTrue($teacherRole->hasPermission('courses.create'));
        $this->assertFalse($teacherRole->hasPermission('users.manage'));

        $studentRole = Role::where('name', 'student')->first();
        $this->assertTrue($studentRole->hasPermission('courses.purchase'));
        $this->assertFalse($studentRole->hasPermission('courses.create'));
    }
}
