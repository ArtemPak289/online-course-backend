<?php

namespace Tests\Unit\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserManagementService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = new UserManagementService;
    }

    public function test_get_paginated_users_filters_by_role_and_blocked(): void
    {
        User::factory()->teacher()->create(['is_blocked' => false]);
        User::factory()->teacher()->blocked()->create();
        User::factory()->student()->count(3)->create();

        $teachers = $this->userService->getPaginatedUsers(['role' => 'teacher']);
        $this->assertSame(2, $teachers->total());

        $blocked = $this->userService->getPaginatedUsers(['is_blocked' => true]);
        $this->assertSame(1, $blocked->total());
    }

    public function test_update_role_modifies_user_role(): void
    {
        $user = User::factory()->student()->create();

        $updatedUser = $this->userService->updateRole($user, UserRole::Teacher);

        $this->assertSame(UserRole::Teacher, $updatedUser->role);
        $this->assertSame(UserRole::Teacher, $user->fresh()->role);
    }

    public function test_update_block_status_blocks_user_and_revokes_tokens(): void
    {
        $user = User::factory()->student()->create(['is_blocked' => false]);
        $token = $user->createToken();

        $this->assertDatabaseHas('api_tokens', [
            'token_hash' => hash('sha256', $token),
        ]);

        $updatedUser = $this->userService->updateBlockStatus($user, true);

        $this->assertTrue($updatedUser->isBlocked());
        $this->assertDatabaseMissing('api_tokens', [
            'token_hash' => hash('sha256', $token),
        ]);
    }

    public function test_update_block_status_unblocks_user(): void
    {
        $user = User::factory()->blocked()->create();

        $updatedUser = $this->userService->updateBlockStatus($user, false);

        $this->assertFalse($updatedUser->isBlocked());
    }
}
