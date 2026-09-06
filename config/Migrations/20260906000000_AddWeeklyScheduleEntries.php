<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddWeeklyScheduleEntries extends BaseMigration
{
    /** Store user-owned schedule details by calendar date. */
    public function change(): void
    {
        $this->table('weekly_schedule_entries')
            ->addColumn('user_id', 'integer')
            ->addColumn('schedule_date', 'date')
            ->addColumn('note', 'text', ['default' => ''])
            ->addColumn('is_inverted', 'boolean', ['default' => false])
            ->addTimestamps()
            ->addIndex(['user_id'])
            ->addIndex(['user_id', 'schedule_date'], ['unique' => true])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->create();
    }
}
