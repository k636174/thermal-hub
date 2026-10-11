<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\WeeklyScheduleEntriesTable;
use Cake\TestSuite\TestCase;

class WeeklyScheduleEntriesTableTest extends TestCase
{
    public function testValidationAllowsNoteLongerThanPreviousLimit(): void
    {
        $table = new WeeklyScheduleEntriesTable();
        $entry = $table->newEntity([
            'schedule_date' => '2026-09-07',
            'note' => str_repeat('予', 121),
            'is_inverted' => true,
        ]);

        $this->assertFalse($entry->hasErrors());
    }
}
