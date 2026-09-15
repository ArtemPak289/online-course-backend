<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Set the currently authenticated API user for the request.
     */
    protected function actingAsApi(User $user): self
    {
        $token = $user->createToken();

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }
}
