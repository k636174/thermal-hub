<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ImagePrintService;
use Imagick;
use PHPUnit\Framework\TestCase;

class ImagePrintServiceTest extends TestCase
{
    public function testPayloadFeedsFullWidthSpaceLinesBeforeCutting(): void
    {
        if (!class_exists('Imagick')) {
            $this->markTestSkipped('Imagick is not installed.');
        }
        $image = new Imagick();
        $image->newImage(10, 10, 'black', 'png');

        $payload = (new ImagePrintService())->buildPayload($image->getImagesBlob(), [
            'printable_width_dots' => 20,
            'label_length_mm' => 100,
            'dpi' => 203,
            'encoding' => 'CP932',
        ]);

        $spaceLines = str_repeat("\x81\x40\n", 8);
        $this->assertStringEndsWith(
            "\x1c\x43\x01\x1c\x26" . $spaceLines . "\x1c\x2e\x1d\x56\x00",
            $payload,
        );
    }
}
