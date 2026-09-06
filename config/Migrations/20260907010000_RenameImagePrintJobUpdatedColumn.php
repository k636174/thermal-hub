<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RenameImagePrintJobUpdatedColumn extends BaseMigration
{
    /** Use CakePHP's conventional modified timestamp column. */
    public function up(): void
    {
        $this->table('image_print_jobs')
            ->renameColumn('updated', 'modified')
            ->update();
    }

    /** Restore the column created by addTimestamps(). */
    public function down(): void
    {
        $this->table('image_print_jobs')
            ->renameColumn('modified', 'updated')
            ->update();
    }
}
