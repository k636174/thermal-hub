<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testPasswordIsHashedAndCanBeVerified(): void
    {
        $user = new User(['password' => 'password123']);

        $this->assertNotSame('password123', $user->password);
        $this->assertTrue(password_verify('password123', $user->password));
    }
}
