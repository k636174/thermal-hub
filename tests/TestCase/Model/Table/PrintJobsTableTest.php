<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PrintJobsTable;
use Cake\TestSuite\TestCase;

class PrintJobsTableTest extends TestCase
{
    public function testPaperGuideAcceptsOnlySupportedValues(): void
    {
        $table = new PrintJobsTable();
        $valid = $table->newEntity(['title' => 'test', 'body' => 'body', 'paper_guide' => 'm5']);
        $invalid = $table->newEntity(['title' => 'test', 'body' => 'body', 'paper_guide' => 'a4']);

        $this->assertFalse($valid->hasErrors());
        $this->assertArrayHasKey('paper_guide', $invalid->getErrors());
    }
}
