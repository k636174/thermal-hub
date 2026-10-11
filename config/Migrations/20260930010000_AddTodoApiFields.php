<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddTodoApiFields extends BaseMigration
{
    /** Add fields required by the Todo JSON API. */
    public function change(): void
    {
        $this->table('todos')
            ->addColumn('description', 'text', ['default' => '', 'after' => 'title'])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'pending', 'after' => 'description'])
            ->addColumn('priority', 'string', ['limit' => 20, 'default' => 'medium', 'after' => 'status'])
            ->changeColumn('due_date', 'datetime', ['null' => true])
            ->addIndex(['user_id', 'status', 'due_date'])
            ->update();
    }
}
