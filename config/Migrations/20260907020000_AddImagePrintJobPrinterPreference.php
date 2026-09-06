<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddImagePrintJobPrinterPreference extends BaseMigration
{
    /** Store the most recently selected printer for each uploaded image. */
    public function change(): void
    {
        $this->table('image_print_jobs')
            ->addColumn('last_printer_id', 'integer', ['null' => true, 'default' => null])
            ->addIndex(['last_printer_id'])
            ->addForeignKey('last_printer_id', 'printers', 'id', ['delete' => 'SET_NULL'])
            ->update();
    }
}
