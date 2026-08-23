<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\EscPosPrinterService;
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

    public function testBuildPayloadConvertsReverseMarkersToEscPosCommands(): void
    {
        $payload = (new EscPosPrinterService())->buildPayload('before !!alert!! after', 'UTF-8');

        $this->assertStringContainsString("before \x1d\x42\x01alert\x1d\x42\x00 after", $payload);
        $this->assertStringNotContainsString('!!', $payload);
    }

    public function testBuildPayloadConvertsQrMarkerToEscPosQrCommands(): void
    {
        $payload = (new EscPosPrinterService())->buildPayload('[[QR:https://example.com]]', 'UTF-8');

        $this->assertStringContainsString("\x1d\x28\x6b\x04\x00\x31\x41\x32\x00", $payload);
        $this->assertStringContainsString("\x1d\x28\x6b\x03\x00\x31\x43\x06", $payload);
        $this->assertStringContainsString('https://example.com', $payload);
        $this->assertStringEndsWith("\x1d\x28\x6b\x03\x00\x31\x51\x30\n\x1b\x64\x03\x1d\x56\x00", $payload);
        $this->assertStringNotContainsString('[[QR:', $payload);
    }
}
