<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'loginuser@example.com',
            'password' => Hash::make('secretpassword123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'loginuser@example.com',
            'password' => 'secretpassword123',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'email', 'name', 'role'],
                'token',
            ])
            ->assertJson([
                'message' => 'Authenticated successfully.',
                'user' => [
                    'id' => $user->id,
                    'email' => 'loginuser@example.com',
                ],
            ]);

        $token = $response->json('token');
        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('api_tokens', [
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
        ]);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'loginuser@example.com',
            'password' => Hash::make('secretpassword123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'loginuser@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'message' => 'Invalid login credentials.',
            ]);
    }

    public function test_login_fails_with_non_existent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'doesnotexist@example.com',
            'password' => 'somepassword',
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'message' => 'Invalid login credentials.',
            ]);
    }

    public function test_blocked_user_cannot_login(): void
    {
        User::factory()->blocked()->create([
            'email' => 'blocked@example.com',
            'password' => Hash::make('secretpassword123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'blocked@example.com',
            'password' => 'secretpassword123',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson([
                'message' => 'Your account has been blocked.',
            ]);
    }

    public function test_login_validation_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
