<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateThermalHub extends BaseMigration
{
    /** 初期テーブルを作成する。 */
    public function change(): void
    {
        $users = $this->table('users');
        $users->addColumn('email', 'string', ['limit' => 255])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('password', 'string', ['limit' => 255])
            ->addTimestamps()
            ->addIndex(['email'], ['unique' => true])
            ->create();

        $printers = $this->table('printers');
        $printers->addColumn('user_id', 'integer')
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('host', 'string', ['limit' => 255])
            ->addColumn('port', 'integer', ['default' => 9100])
            ->addColumn('encoding', 'string', ['limit' => 30, 'default' => 'CP932'])
            ->addColumn('timeout', 'integer', ['default' => 5])
            ->addTimestamps()
            ->addIndex(['user_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->create();

        $jobs = $this->table('print_jobs');
        $jobs->addColumn('user_id', 'integer')
            ->addColumn('title', 'string', ['limit' => 200])
            ->addColumn('body', 'text')
            ->addTimestamps()
            ->addIndex(['user_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->create();

        $logs = $this->table('print_logs');
        $logs->addColumn('user_id', 'integer')
            ->addColumn('printer_id', 'integer')
            ->addColumn('print_job_id', 'integer')
            ->addColumn('status', 'string', ['limit' => 20])
            ->addColumn('message', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('printed_at', 'datetime')
            ->addColumn('created', 'datetime')
            ->addIndex(['user_id'])->addIndex(['printer_id'])->addIndex(['print_job_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('printer_id', 'printers', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('print_job_id', 'print_jobs', 'id', ['delete' => 'CASCADE'])
            ->create();
    }
}
