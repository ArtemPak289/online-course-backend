<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Установить текущего аутентифицированного пользователя API для запроса.
     */
    protected function actingAsApi(User $user): self
    {
        $token = $user->createToken();

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }
}
