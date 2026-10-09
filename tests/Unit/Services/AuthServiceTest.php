<?php

namespace Tests\Unit\Services;

use App\Enums\UserRole;
use App\Events\UserRegistered;
use App\Exceptions\AccountBlockedException;
use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService;
    }

    public function test_register_creates_user_and_dispatches_event(): void
    {
        Event::fake([UserRegistered::class]);

        $result = $this->authService->register([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertSame('john@example.com', $result['user']->email);
        $this->assertSame(UserRole::Student, $result['user']->role);
        $this->assertNotEmpty($result['token']);

        Event::assertDispatched(UserRegistered::class);
    }

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'valid@example.com',
            'password' => Hash::make('password123'),
        ]);

        $result = $this->authService->login('valid@example.com', 'password123');

        $this->assertSame($user->id, $result['user']->id);
        $this->assertNotEmpty($result['token']);
    }

    public function test_login_throws_on_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'wrongpass@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->expectException(InvalidCredentialsException::class);

        $this->authService->login('wrongpass@example.com', 'wrongpassword');
    }

    public function test_login_throws_on_non_existent_email(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $this->authService->login('notfound@example.com', 'password123');
    }

    public function test_login_throws_when_account_is_blocked(): void
    {
        User::factory()->blocked()->create([
            'email' => 'blocked@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->expectException(AccountBlockedException::class);

        $this->authService->login('blocked@example.com', 'password123');
    }

    public function test_logout_deletes_current_access_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken();
        $apiToken = $user->tokens()->first();
        $user->withAccessToken($apiToken);

        $this->authService->logout($user);

        $this->assertDatabaseMissing('api_tokens', [
            'id' => $apiToken->id,
        ]);
    }
}
