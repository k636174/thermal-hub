<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAddressLabels extends BaseMigration
{
    /** Add address labels and raster printer settings. */
    public function change(): void
    {
        $this->table('address_labels')
            ->addColumn('user_id', 'integer')
            ->addColumn('title', 'string', ['limit' => 200])
            ->addColumn('postal_code', 'string', ['limit' => 7, 'null' => true])
            ->addColumn('address_line1', 'string', ['limit' => 255])
            ->addColumn('address_line2', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('recipient_name', 'string', ['limit' => 200])
            ->addColumn('honorific', 'string', ['limit' => 20, 'default' => '様'])
            ->addTimestamps()
            ->addIndex(['user_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('printers')
            ->addColumn('dpi', 'integer', ['default' => 203])
            ->addColumn('printable_width_dots', 'integer', ['default' => 576])
            ->addColumn('label_width_mm', 'decimal', ['precision' => 6, 'scale' => 1, 'default' => 72.0])
            ->addColumn('label_length_mm', 'decimal', ['precision' => 6, 'scale' => 1, 'default' => 100.0])
            ->addColumn('raster_enabled', 'boolean', ['default' => true])
            ->update();

        $this->table('print_logs')
            ->changeColumn('print_job_id', 'integer', ['null' => true])
            ->addColumn('address_label_id', 'integer', ['null' => true])
            ->addColumn('document_type', 'string', ['limit' => 20, 'default' => 'text'])
            ->addIndex(['address_label_id'])
            ->addForeignKey('address_label_id', 'address_labels', 'id', ['delete' => 'SET_NULL'])
            ->update();
    }
}
