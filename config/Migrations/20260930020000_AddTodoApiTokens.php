<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddTodoApiTokens extends BaseMigration
{
    /** Store one hashed Todo API token per user. */
    public function change(): void
    {
        $this->table('todo_api_tokens')
            ->addColumn('user_id', 'integer')
            ->addColumn('token_hash', 'string', ['limit' => 64])
            ->addColumn('token_hint', 'string', ['limit' => 12])
            ->addTimestamps()
            ->addIndex(['user_id'], ['unique' => true])
            ->addIndex(['token_hash'], ['unique' => true])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->create();
    }
}
