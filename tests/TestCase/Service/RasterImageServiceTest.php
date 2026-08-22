<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\RasterImageService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RasterImageServiceTest extends TestCase
{
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
