<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddImagePrintJobs extends BaseMigration
{
    /** Add persistent, user-owned uploaded images and image print logging. */
    public function change(): void
    {
        $this->table('image_print_jobs')
            ->addColumn('user_id', 'integer')
            ->addColumn('title', 'string', ['limit' => 200])
            ->addColumn('storage_name', 'string', ['limit' => 80])
            ->addColumn('original_name', 'string', ['limit' => 255])
            ->addColumn('mime_type', 'string', ['limit' => 30])
            ->addColumn('file_size', 'integer')
            ->addTimestamps()
            ->addIndex(['user_id'])
            ->addIndex(['storage_name'], ['unique' => true])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('print_logs')
            ->addColumn('image_print_job_id', 'integer', ['null' => true])
            ->addIndex(['image_print_job_id'])
            ->addForeignKey('image_print_job_id', 'image_print_jobs', 'id', ['delete' => 'SET_NULL'])
            ->update();
    }
}
