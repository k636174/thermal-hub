<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TodosTable;
use Cake\TestSuite\TestCase;

class TodosTableTest extends TestCase
{
    public function testDescriptionStatusAndPriorityValidation(): void
    {
        $table = new TodosTable();
        $valid = $table->newEntity([
            'title' => '牛乳を買う',
            'description' => '低脂肪乳を2本買う',
            'status' => 'pending',
            'priority' => 'high',
        ]);
        $invalid = $table->newEntity([
            'title' => '無効なTodo',
            'status' => 'waiting',
            'priority' => 'urgent',
        ]);

        $this->assertFalse($valid->hasErrors());
        $this->assertArrayHasKey('status', $invalid->getErrors());
        $this->assertArrayHasKey('priority', $invalid->getErrors());
    }
}
