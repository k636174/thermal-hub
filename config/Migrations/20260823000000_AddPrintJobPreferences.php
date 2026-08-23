<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPrintJobPreferences extends BaseMigration
{
    /** Store the most recently successful print choices for each print job. */
    public function change(): void
    {
        $this->table('print_jobs')
            ->addColumn('paper_guide', 'string', ['limit' => 20, 'default' => 'none', 'null' => false])
            ->addColumn('last_printer_id', 'integer', ['null' => true, 'default' => null])
            ->addColumn('last_paper_guide', 'string', ['limit' => 20, 'null' => true, 'default' => null])
            ->addIndex(['last_printer_id'])
            ->addForeignKey('last_printer_id', 'printers', 'id', ['delete' => 'SET_NULL'])
            ->update();
    }
}
