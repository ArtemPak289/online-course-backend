<?php

namespace Tests\Unit;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_token_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $token = ApiToken::create([
            'user_id' => $user->id,
            'name' => 'test',
            'token_hash' => hash('sha256', 'sample_token'),
        ]);

        $this->assertInstanceOf(User::class, $token->user);
        $this->assertSame($user->id, $token->user->id);
    }

    public function test_api_token_validity_with_no_expiration(): void
    {
        $token = new ApiToken(['expires_at' => null]);
        $this->assertTrue($token->isValid());
    }

    public function test_api_token_validity_with_future_expiration(): void
    {
        $token = new ApiToken(['expires_at' => now()->addDay()]);
        $this->assertTrue($token->isValid());
    }

    public function test_api_token_validity_with_past_expiration(): void
    {
        $token = new ApiToken(['expires_at' => now()->subDay()]);
        $this->assertFalse($token->isValid());
    }
}
