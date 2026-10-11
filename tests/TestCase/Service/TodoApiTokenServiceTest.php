<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\TodoApiTokenService;
use Cake\Utility\Security;
use PHPUnit\Framework\TestCase;

class TodoApiTokenServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Security::setSalt('test-salt-for-todo-api-token-service');
    }

    public function testGeneratedTokenAndHashAreSafeToStore(): void
    {
        $service = new TodoApiTokenService();
        $token = $service->generate();

        $this->assertSame(64, strlen($token));
        $this->assertSame(64, strlen($service->hash($token)));
        $this->assertNotSame($token, $service->hash($token));
        $this->assertStringEndsWith(substr($token, -8), $service->hint($token));
    }
}
