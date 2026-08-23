<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\EscPosPrinterService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EscPosPrinterServiceTest extends TestCase
{
    public function testBuildPayloadAddsEscPosCommandsAndNormalizesNewlines(): void
    {
        $payload = (new EscPosPrinterService())->buildPayload("hello\r\nworld", 'UTF-8');
        $this->assertSame("\x1b\x40hello\nworld\n\x1b\x64\x03\x1d\x56\x00", $payload);
    }

    public function testBuildPayloadEnablesShiftJisKanjiModeForCp932(): void
    {
        $payload = (new EscPosPrinterService())->buildPayload('日本語', 'CP932');

        $this->assertSame(
            "\x1b\x40\x1c\x43\x01\x1c\x26" . iconv('UTF-8', 'CP932', '日本語')
                . "\x1c\x2e\n\x1b\x64\x03\x1d\x56\x00",
            $payload,
        );
    }

    public function testBuildPayloadEnablesShiftJisKanjiModeForShiftJis(): void
    {
        $payload = (new EscPosPrinterService())->buildPayload('日本語', 'SHIFT_JIS');

        $this->assertStringStartsWith("\x1b\x40\x1c\x43\x01\x1c\x26", $payload);
        $this->assertStringContainsString("\x1c\x2e\n", $payload);
    }

    public function testBuildPayloadFeedsToM5Length(): void
    {
        $payload = (new EscPosPrinterService())->buildPayload('hello', 'UTF-8', 'm5');

        // Calibrated target is 977 dots. One printed line uses 30 dots, leaving 947 dots.
        $this->assertSame(
            "\x1b\x40hello\n\x1b\x4a\xff\x1b\x4a\xff\x1b\x4a\xff\x1b\x4a\xb6\x1d\x56\x00",
            $payload,
        );
    }

    public function testBuildPayloadFeedsToNarrowLength(): void
    {
        $payload = (new EscPosPrinterService())->buildPayload("one\ntwo", 'UTF-8', 'narrow');

        // Calibrated target is 1,582 dots. Two printed lines use 60 dots, leaving 1,522 dots.
        $this->assertSame(
            "\x1b\x40one\ntwo\n\x1b\x4a\xff\x1b\x4a\xff\x1b\x4a\xff\x1b\x4a\xff\x1b\x4a\xff\x1b\x4a\xf7\x1d\x56\x00",
            $payload,
        );
    }

    public function testBuildPayloadCutsLongFixedLengthBodyIntoMultiplePages(): void
    {
        $body = implode("\n", array_fill(0, 40, 'line'));
        $payload = (new EscPosPrinterService())->buildPayload($body, 'UTF-8', 'm5');

        $this->assertSame(2, substr_count($payload, "\x1d\x56\x00"));
        $this->assertSame(2, substr_count($payload, "\x1b\x40"));
    }

    public function testBuildPayloadAccountsForWrappedLinesWhenPaginating(): void
    {
        $body = implode("\n", array_fill(0, 17, str_repeat('あ', 25)));
        $payload = (new EscPosPrinterService())->buildPayload($body, 'UTF-8', 'm5');

        // Each 25-character Japanese line wraps into two 48-column rows: 34 rows total.
        $this->assertSame(2, substr_count($payload, "\x1d\x56\x00"));
    }

    public function testBuildPayloadRejectsUnknownPaperLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new EscPosPrinterService())->buildPayload('hello', 'UTF-8', 'unknown');
    }
}
