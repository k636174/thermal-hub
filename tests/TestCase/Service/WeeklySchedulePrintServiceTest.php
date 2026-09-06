<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\WeeklySchedulePrintService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class WeeklySchedulePrintServiceTest extends TestCase
{
    public function testBuildPayloadPrintsSevenDaysWithoutHeader(): void
    {
        $payload = (new WeeklySchedulePrintService())->buildPayload(
            new DateTimeImmutable('2026-09-07'),
            ['朝会', '資料レビュー'],
            [5, 6],
            'UTF-8',
        );

        $this->assertStringNotContainsString('2026年9月7日 - 9月13日', $payload);
        $this->assertStringContainsString("2026/09/07 (Mon) ---------------------------\n　　朝会", $payload);
        $this->assertStringContainsString('2026/09/13 (Sun)', $payload);
        $this->assertStringContainsString("\x1d\x42\x01　2026/09/12 (Sat)　\x1d\x42\x00", $payload);
        $this->assertSame(7, substr_count($payload, ' ---------------------------'));
        $this->assertSame(2, substr_count($payload, "\x1d\x42\x01"));
        $this->assertStringStartsWith("\x1b\x40\x1b\x33\x18", $payload);
        $this->assertStringEndsWith("\x1b\x4a\xa2\x1d\x56\x00", $payload);
        $this->assertSame(94, substr_count($payload, '　'));
        $this->assertPaperTravelIncludesThreeCalibrationLines($payload);
    }

    public function testBuildPayloadLeavesScheduleMarkupLiteral(): void
    {
        $payload = (new WeeklySchedulePrintService())->buildPayload(
            new DateTimeImmutable('2026-09-07'),
            ['!!重要!!'],
            [],
            'UTF-8',
        );

        $this->assertStringContainsString('!!重要!!', $payload);
        $this->assertStringNotContainsString("\x1d\x42\x01", $payload);
    }

    public function testBuildPayloadWrapsLongJapaneseNoteWithinFixedDayHeight(): void
    {
        $note = str_repeat('予', 70);
        $payload = (new WeeklySchedulePrintService())->buildPayload(
            new DateTimeImmutable('2026-09-07'),
            [$note],
            [],
            'UTF-8',
        );

        $this->assertSame(70, substr_count($payload, '予'));
        $this->assertPaperTravelIncludesThreeCalibrationLines($payload);
    }

    public function testSundayNoteIsFollowedByTwentyMillimeterCutMargin(): void
    {
        $payload = (new WeeklySchedulePrintService())->buildPayload(
            new DateTimeImmutable('2026-09-07'),
            [6 => '日曜の予定'],
            [],
            'UTF-8',
        );

        $this->assertStringContainsString("　　日曜の予定\n　　\n　　\n　　\n　　\n　　\n\x1b\x4a\x03", $payload);
        $this->assertStringEndsWith("\x1b\x4a\xa2\x1d\x56\x00", $payload);
        $this->assertPaperTravelIncludesThreeCalibrationLines($payload);
    }

    private function assertPaperTravelIncludesThreeCalibrationLines(string $payload): void
    {
        preg_match_all('/\x1b\x4a(.)/s', $payload, $matches);
        $dotFeeds = array_sum(array_map('ord', $matches[1]));
        $lineFeeds = substr_count($payload, "\n") * 24;

        $this->assertSame(1431, $lineFeeds + $dotFeeds);
    }
}
