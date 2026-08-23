<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class BackfillPrintJobPreferences extends BaseMigration
{
    /** Initialize preferences from data recorded before preference columns existed. */
    public function up(): void
    {
        $this->execute(
            'UPDATE print_jobs SET last_printer_id = (' .
            'SELECT print_logs.printer_id FROM print_logs ' .
            'WHERE print_logs.print_job_id = print_jobs.id ' .
            'ORDER BY print_logs.printed_at DESC, print_logs.id DESC LIMIT 1' .
            ') WHERE last_printer_id IS NULL AND EXISTS (' .
            'SELECT 1 FROM print_logs WHERE print_logs.print_job_id = print_jobs.id' .
            ')',
        );
        $this->execute(
            "UPDATE print_jobs SET last_paper_guide = COALESCE(last_paper_guide, paper_guide, 'none') " .
            'WHERE last_paper_guide IS NULL',
        );
    }

    /** Backfilled user preferences are intentionally retained on rollback. */
    public function down(): void
    {
    }
}
