<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Events\UserRegistered;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_successfully(): void
    {
        Event::fake([UserRegistered::class]);

        $payload = [
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'password' => 'securepassword123',
            'password_confirmation' => 'securepassword123',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'is_blocked',
                    'created_at',
                    'updated_at',
                ],
                'token',
            ])
            ->assertJson([
                'message' => 'User registered successfully.',
                'user' => [
                    'name' => 'Alice Smith',
                    'email' => 'alice@example.com',
                    'role' => UserRole::Student->value,
                    'is_blocked' => false,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'alice@example.com',
            'role' => UserRole::Student->value,
            'is_blocked' => false,
        ]);

        $this->assertNotEmpty($response->json('token'));

        Event::assertDispatched(UserRegistered::class, function ($event) {
            return $event->user->email === 'alice@example.com';
        });
    }

    public function test_registration_validation_fails_on_missing_fields(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registration_validation_fails_on_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Bob Test',
            'email' => 'existing@example.com',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_validation_fails_on_short_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Short Pass',
            'email' => 'short@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_validation_fails_on_password_mismatch(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Mismatch Pass',
            'email' => 'mismatch@example.com',
            'password' => 'secret12345',
            'password_confirmation' => 'different12345',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['password']);
    }
}
