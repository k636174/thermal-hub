<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddTodos extends BaseMigration
{
    /** Add user-owned todos and their last print settings. */
    public function change(): void
    {
        $this->table('todos')
            ->addColumn('user_id', 'integer')
            ->addColumn('title', 'string', ['limit' => 500])
            ->addColumn('due_date', 'date', ['null' => true])
            ->addColumn('is_completed', 'boolean', ['default' => false])
            ->addTimestamps()
            ->addIndex(['user_id'])
            ->addIndex(['user_id', 'is_completed', 'due_date'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('todo_print_preferences')
            ->addColumn('user_id', 'integer')
            ->addColumn('printer_id', 'integer', ['null' => true])
            ->addColumn('paper_guide', 'string', ['limit' => 20, 'default' => 'narrow'])
            ->addTimestamps()
            ->addIndex(['user_id'], ['unique' => true])
            ->addIndex(['printer_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('printer_id', 'printers', 'id', ['delete' => 'SET_NULL'])
            ->create();
    }
}
