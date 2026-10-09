<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfileService $profileService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->profileService = new ProfileService;
    }

    public function test_update_profile_updates_attributes(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $updatedUser = $this->profileService->updateProfile($user, [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $this->assertSame('New Name', $updatedUser->name);
        $this->assertSame('new@example.com', $updatedUser->email);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_change_password_updates_user_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        $this->profileService->changePassword($user, 'newpassword123');

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }
}
