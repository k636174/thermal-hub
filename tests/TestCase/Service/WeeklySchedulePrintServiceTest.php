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
        $this->assertStringContainsString("2026/09/07 (Mon) ---------------------------\n　｜朝会", $payload);
        $this->assertStringContainsString('2026/09/13 (Sun)', $payload);
        $this->assertStringContainsString("\x1d\x42\x01　2026/09/12 (Sat)　\x1d\x42\x00", $payload);
        $this->assertSame(7, substr_count($payload, ' ---------------------------'));
        $this->assertSame(2, substr_count($payload, "\x1d\x42\x01"));
        $this->assertStringStartsWith("\x1b\x40\x1b\x33\x18", $payload);
        $this->assertStringEndsWith("\x1b\x4a\xa2\x1d\x56\x00", $payload);
        $this->assertSame(49, substr_count($payload, '　'));
        $this->assertSame(45, substr_count($payload, '｜'));
        $this->assertPaperTravelIncludesThreeCalibrationLines($payload);
    }

    public function testBuildPayloadReversesTextEnclosedInExclamationMarks(): void
    {
        $payload = (new WeeklySchedulePrintService())->buildPayload(
            new DateTimeImmutable('2026-09-07'),
            ['!!重要!!'],
            [],
            'UTF-8',
        );

        $this->assertStringNotContainsString('!!', $payload);
        $this->assertStringContainsString("　｜\x1d\x42\x01重要\x1d\x42\x00\n", $payload);
        $this->assertSame(1, substr_count($payload, "\x1d\x42\x01"));
    }

    public function testBuildPayloadKeepsReverseMarkupAcrossWrappedLines(): void
    {
        $payload = (new WeeklySchedulePrintService())->buildPayload(
            new DateTimeImmutable('2026-09-07'),
            ['!!' . str_repeat('重', 30) . '!!'],
            [],
            'UTF-8',
        );

        $this->assertStringNotContainsString('!!', $payload);
        $this->assertSame(30, substr_count($payload, '重'));
        $this->assertSame(2, substr_count($payload, "\x1d\x42\x01"));
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

        $this->assertStringContainsString("　｜日曜の予定\n　｜\n　｜\n　｜\n　｜\n　｜\n\x1b\x4a\x03", $payload);
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
