<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\RasterImageService;
use Imagick;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RasterImageServiceTest extends TestCase
{
    public function testOrientForPreviewRestoresHorizontalDimensions(): void
    {
        if (!class_exists('Imagick')) {
            $this->markTestSkipped('Imagick is not installed.');
        }
        $rotated = new Imagick();
        $rotated->newImage(5, 9, 'white', 'png');

        $preview = new Imagick();
        $preview->readImageBlob((new RasterImageService())->orientForPreview($rotated->getImagesBlob(), 4));

        $this->assertSame(9, $preview->getImageWidth());
        $this->assertSame(4, $preview->getImageHeight());
    }

    public function testBitmapToEscPosPacksMostSignificantBitFirst(): void
    {
        $payload = (new RasterImageService())->bitmapToEscPos([
            [true, false, true, false, false, false, false, true, true],
        ]);

        $this->assertSame("\x1d\x76\x30\x00\x02\x00\x01\x00\xa1\x80", $payload);
    }

    public function testBitmapToEscPosRejectsUnevenRows(): void
    {
        $this->expectException(RuntimeException::class);
        (new RasterImageService())->bitmapToEscPos([[true, false], [true]]);
    }
}
