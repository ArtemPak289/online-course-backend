<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $plainToken = $user->createToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->postJson('/api/logout');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Logged out successfully.',
            ]);

        $this->assertDatabaseMissing('api_tokens', [
            'token_hash' => hash('sha256', $plainToken),
        ]);
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_logged_out_token_is_rejected_on_subsequent_requests(): void
    {
        $user = User::factory()->create();
        $plainToken = $user->createToken();

        // Выход из системы
        $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->postJson('/api/logout')
            ->assertStatus(Response::HTTP_OK);

        // Попытка доступа к профилю с тем же токеном
        $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->getJson('/api/profile')
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
