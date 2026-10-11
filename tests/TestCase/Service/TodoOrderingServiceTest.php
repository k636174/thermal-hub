<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\TodoOrderingService;
use Cake\ORM\Entity;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class TodoOrderingServiceTest extends TestCase
{
    public function testSortBalancesDeadlinePriorityAndCompletion(): void
    {
        $todos = [
            new Entity(['id' => 1, 'priority' => 'high', 'status' => 'pending', 'due_date' => null]),
            new Entity(['id' => 2, 'priority' => 'low', 'status' => 'pending', 'due_date' => '2026-10-01']),
            new Entity(['id' => 3, 'priority' => 'high', 'status' => 'completed', 'due_date' => '2026-09-01']),
            new Entity(['id' => 4, 'priority' => 'high', 'status' => 'pending', 'due_date' => '2026-10-02']),
        ];

        $sorted = (new TodoOrderingService())->sort($todos, new DateTimeImmutable('2026-09-30'));

        $this->assertSame([4, 2, 1, 3], array_map(
            static fn(Entity $todo): int => (int)$todo->get('id'),
            $sorted,
        ));
    }
}
