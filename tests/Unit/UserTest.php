<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_helpers_return_correct_boolean(): void
    {
        $admin = User::factory()->admin()->make();
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isTeacher());
        $this->assertFalse($admin->isStudent());
        $this->assertTrue($admin->hasRole(UserRole::Admin));
        $this->assertTrue($admin->hasRole('admin'));

        $teacher = User::factory()->teacher()->make();
        $this->assertFalse($teacher->isAdmin());
        $this->assertTrue($teacher->isTeacher());
        $this->assertFalse($teacher->isStudent());

        $student = User::factory()->student()->make();
        $this->assertFalse($student->isAdmin());
        $this->assertFalse($student->isTeacher());
        $this->assertTrue($student->isStudent());
    }

    public function test_user_blocked_helper(): void
    {
        $activeUser = User::factory()->make(['is_blocked' => false]);
        $this->assertFalse($activeUser->isBlocked());

        $blockedUser = User::factory()->blocked()->make();
        $this->assertTrue($blockedUser->isBlocked());
    }

    public function test_create_token_stores_sha256_hash_and_returns_plain_text(): void
    {
        $user = User::factory()->create();

        $plainToken = $user->createToken('test_token');

        $this->assertNotEmpty($plainToken);
        $this->assertDatabaseHas('api_tokens', [
            'user_id' => $user->id,
            'name' => 'test_token',
            'token_hash' => hash('sha256', $plainToken),
        ]);
    }
}
