<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_users_list(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->teacher()->count(2)->create();
        User::factory()->student()->count(3)->create();

        $response = $this->actingAsApi($admin)
            ->getJson('/api/admin/users');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'role', 'is_blocked'],
                ],
                'current_page',
                'total',
            ]);

        $this->assertEquals(6, $response->json('total'));
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->teacher()->count(2)->create();
        User::factory()->student()->count(3)->create();

        $response = $this->actingAsApi($admin)
            ->getJson('/api/admin/users?role=teacher');

        $response->assertStatus(Response::HTTP_OK);
        $this->assertEquals(2, $response->json('total'));
        foreach ($response->json('data') as $item) {
            $this->assertSame('teacher', $item['role']);
        }
    }

    public function test_student_cannot_view_users_list(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAsApi($student)
            ->getJson('/api/admin/users');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_teacher_cannot_view_users_list(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAsApi($teacher)
            ->getJson('/api/admin/users');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_admin_can_update_user_role(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->student()->create();

        $response = $this->actingAsApi($admin)
            ->patchJson("/api/admin/users/{$targetUser->id}/role", [
                'role' => UserRole::Teacher->value,
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'User role updated successfully.',
                'user' => [
                    'id' => $targetUser->id,
                    'role' => UserRole::Teacher->value,
                ],
            ]);

        $this->assertSame(UserRole::Teacher, $targetUser->fresh()->role);
    }

    public function test_non_admin_cannot_update_user_role(): void
    {
        $teacher = User::factory()->teacher()->create();
        $targetUser = User::factory()->student()->create();

        $response = $this->actingAsApi($teacher)
            ->patchJson("/api/admin/users/{$targetUser->id}/role", [
                'role' => UserRole::Admin->value,
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $this->assertSame(UserRole::Student, $targetUser->fresh()->role);
    }

    public function test_role_update_fails_with_invalid_role(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->student()->create();

        $response = $this->actingAsApi($admin)
            ->patchJson("/api/admin/users/{$targetUser->id}/role", [
                'role' => 'superhero',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_admin_can_block_and_unblock_user(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->student()->create();
        $targetToken = $targetUser->createToken();

        // Блокировка пользователя
        $response = $this->actingAsApi($admin)
            ->patchJson("/api/admin/users/{$targetUser->id}/block", [
                'is_blocked' => true,
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'User blocked successfully.',
                'user' => [
                    'id' => $targetUser->id,
                    'is_blocked' => true,
                ],
            ]);

        $this->assertTrue($targetUser->fresh()->isBlocked());

        // Существующий токен должен быть удален
        $this->assertDatabaseMissing('api_tokens', [
            'token_hash' => hash('sha256', $targetToken),
        ]);

        // Разблокировка пользователя
        $unblockResponse = $this->actingAsApi($admin)
            ->patchJson("/api/admin/users/{$targetUser->id}/block", [
                'is_blocked' => false,
            ]);

        $unblockResponse->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'User unblocked successfully.',
                'user' => [
                    'id' => $targetUser->id,
                    'is_blocked' => false,
                ],
            ]);

        $this->assertFalse($targetUser->fresh()->isBlocked());
    }

    public function test_admin_cannot_block_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAsApi($admin)
            ->patchJson("/api/admin/users/{$admin->id}/block", [
                'is_blocked' => true,
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $this->assertFalse($admin->fresh()->isBlocked());
    }

    public function test_blocked_user_token_is_denied_access_to_endpoints(): void
    {
        $user = User::factory()->student()->create(['is_blocked' => false]);
        $plainToken = $user->createToken();

        // Вручную отмечаем пользователя как заблокированного в базе данных
        $user->update(['is_blocked' => true]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->getJson('/api/profile');

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson([
                'message' => 'Your account has been blocked.',
            ]);
    }
}
