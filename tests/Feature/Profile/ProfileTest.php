<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_own_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Profile Owner',
            'email' => 'owner@example.com',
        ]);

        $response = $this->actingAsApi($user)
            ->getJson('/api/profile');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => 'Profile Owner',
                    'email' => 'owner@example.com',
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_view_profile(): void
    {
        $response = $this->getJson('/api/profile');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $response = $this->actingAsApi($user)
            ->putJson('/api/profile', [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Profile updated successfully.',
                'user' => [
                    'id' => $user->id,
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_user_can_update_profile_keeping_own_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Same Email User',
            'email' => 'same@example.com',
        ]);

        $response = $this->actingAsApi($user)
            ->putJson('/api/profile', [
                'name' => 'New Name Only',
                'email' => 'same@example.com',
            ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name Only',
            'email' => 'same@example.com',
        ]);
    }

    public function test_user_cannot_update_profile_to_another_users_email(): void
    {
        User::factory()->create(['email' => 'other@example.com']);
        $user = User::factory()->create(['email' => 'me@example.com']);

        $response = $this->actingAsApi($user)
            ->putJson('/api/profile', [
                'name' => 'My New Name',
                'email' => 'other@example.com',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);

        $response = $this->actingAsApi($user)
            ->putJson('/api/profile/password', [
                'current_password' => 'oldpassword123',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Password changed successfully.',
            ]);

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_user_cannot_change_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);

        $response = $this->actingAsApi($user)
            ->putJson('/api/profile/password', [
                'current_password' => 'incorrectpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_user_cannot_change_password_when_confirmation_does_not_match(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);

        $response = $this->actingAsApi($user)
            ->putJson('/api/profile/password', [
                'current_password' => 'oldpassword123',
                'password' => 'newpassword123',
                'password_confirmation' => 'mismatched123',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_user_cannot_change_password_to_the_same_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);

        $response = $this->actingAsApi($user)
            ->putJson('/api/profile/password', [
                'current_password' => 'oldpassword123',
                'password' => 'oldpassword123',
                'password_confirmation' => 'oldpassword123',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['password']);
    }
}
