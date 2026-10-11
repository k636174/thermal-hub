<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\TodoPrintService;
use Cake\I18n\Date;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TodoPrintServiceTest extends TestCase
{
    public function testBuildPayloadPrintsStatusTitleAndDueDate(): void
    {
        $payload = (new TodoPrintService())->buildPayload([
            ['title' => '資料を仕上げる', 'due_date' => new Date('2026-10-03'), 'is_completed' => false],
            ['title' => '予約する', 'due_date' => null, 'is_completed' => true],
        ], 'narrow', 'UTF-8', 203);

        $this->assertStringContainsString('TODO', $payload);
        $this->assertStringContainsString('[ ] 資料を仕上げる 10/03', $payload);
        $this->assertStringContainsString('[x] 予約する', $payload);
        $this->assertStringEndsWith("\x1b\x4a\xa0\x1d\x56\x00", $payload);
    }

    public function testM5RejectsMoreThanSevenItems(): void
    {
        $this->expectException(RuntimeException::class);
        (new TodoPrintService())->buildPayload(
            array_fill(0, 8, ['title' => 'Todo', 'due_date' => null, 'is_completed' => false]),
            'm5',
            'UTF-8',
        );
    }
}
