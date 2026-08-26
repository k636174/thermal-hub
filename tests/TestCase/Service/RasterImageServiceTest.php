<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\AddressLabelLayoutService;
use App\Service\RasterImageService;
use Imagick;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RasterImageServiceTest extends TestCase
{
    public function testRenderRasterizesJapaneseGlyphs(): void
    {
        if (!class_exists('Imagick')) {
            $this->markTestSkipped('Imagick is not installed.');
        }
        $layout = new AddressLabelLayoutService();
        $svg = $layout->renderSvg([
            'address_line1' => '東京都千代田区',
            'recipient_name' => '山田太郎',
            'honorific' => '様',
        ], 203, 72.0, 100.0);

        $rendered = (new RasterImageService())->render($svg, 576);
        $image = new Imagick();
        $image->readImageBlob($rendered['png']);
        $blackPixels = 0;
        $intensities = $image->exportImagePixels(
            0,
            0,
            $image->getImageWidth(),
            $image->getImageHeight(),
            'I',
            Imagick::PIXEL_CHAR,
        );
        foreach ($intensities as $intensity) {
            if ((int)$intensity < 128) {
                $blackPixels++;
            }
        }

        $this->assertGreaterThan(0, $blackPixels, 'Japanese glyphs were not rasterized.');
    }

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
