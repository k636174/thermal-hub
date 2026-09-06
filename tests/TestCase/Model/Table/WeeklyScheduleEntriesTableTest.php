<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\WeeklyScheduleEntriesTable;
use Cake\TestSuite\TestCase;

class WeeklyScheduleEntriesTableTest extends TestCase
{
    public function testValidationLimitsNoteLength(): void
    {
        $table = new WeeklyScheduleEntriesTable();
        $valid = $table->newEntity([
            'schedule_date' => '2026-09-07',
            'note' => str_repeat('予', 120),
            'is_inverted' => true,
        ]);
        $invalid = $table->newEntity([
            'schedule_date' => '2026-09-07',
            'note' => str_repeat('予', 121),
            'is_inverted' => false,
        ]);

        $this->assertFalse($valid->hasErrors());
        $this->assertArrayHasKey('note', $invalid->getErrors());
    }
}
