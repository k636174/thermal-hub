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

    public function testBuildPayloadFeedsToM5Length(): void
    {
        $payload = (new EscPosPrinterService())->buildPayload('hello', 'UTF-8', 'm5');

        // 105mm at 203dpi = 839 dots. One printed line uses 30 dots, leaving 809 dots.
        $this->assertSame(
            "\x1b\x40hello\n\x1b\x4a\xff\x1b\x4a\xff\x1b\x4a\xff\x1b\x4a\x2c\x1d\x56\x00",
            $payload,
        );
    }

    public function testBuildPayloadFeedsToNarrowLength(): void
    {
        $payload = (new EscPosPrinterService())->buildPayload("one\ntwo", 'UTF-8', 'narrow');

        // 170mm at 203dpi = 1,359 dots. Two printed lines use 60 dots, leaving 1,299 dots.
        $this->assertSame(
            "\x1b\x40one\ntwo\n\x1b\x4a\xff\x1b\x4a\xff\x1b\x4a\xff\x1b\x4a\xff\x1b\x4a\xff\x1b\x4a\x18\x1d\x56\x00",
            $payload,
        );
    }

    public function testBuildPayloadFallsBackToThreeLinesWhenBodyExceedsFixedLength(): void
    {
        $body = implode("\n", array_fill(0, 30, 'line'));
        $payload = (new EscPosPrinterService())->buildPayload($body, 'UTF-8', 'm5');

        $this->assertStringEndsWith("\n\x1b\x64\x03\x1d\x56\x00", $payload);
    }

    public function testBuildPayloadRejectsUnknownPaperLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new EscPosPrinterService())->buildPayload('hello', 'UTF-8', 'unknown');
    }
}
