<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_user_roles_have_expected_values(): void
    {
        $this->assertSame('admin', UserRole::Admin->value);
        $this->assertSame('teacher', UserRole::Teacher->value);
        $this->assertSame('student', UserRole::Student->value);
    }

    public function test_values_method_returns_all_role_strings(): void
    {
        $this->assertEqualsCanonicalizing(
            ['admin', 'teacher', 'student'],
            UserRole::values()
        );
    }
}
