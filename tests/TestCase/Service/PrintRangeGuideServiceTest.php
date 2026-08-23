<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\PrintRangeGuideService;
use PHPUnit\Framework\TestCase;

class PrintRangeGuideServiceTest extends TestCase
{
    public function testAnalyzeCountsInputAndWrappedLines(): void
    {
        $guide = (new PrintRangeGuideService())->analyze("abc\n" . str_repeat('あ', 25));

        $this->assertSame(2, $guide['inputLines']);
        $this->assertSame(28, $guide['characters']);
        $this->assertSame(50, $guide['maxColumns']);
        $this->assertSame(3, $guide['wrappedLines']);
        $this->assertSame(0, $guide['qrCodes']);
    }

    public function testAnalyzeDoesNotCountMarkupDelimitersAsCharacters(): void
    {
        $guide = (new PrintRangeGuideService())->analyze('before !!alert!! after');

        $this->assertSame(18, $guide['characters']);
        $this->assertSame(18, $guide['maxColumns']);
        $this->assertSame(1, $guide['wrappedLines']);
    }

    public function testAnalyzeReportsQrCodesSeparately(): void
    {
        $guide = (new PrintRangeGuideService())->analyze("[[QR:https://example.com]]\ntext");

        $this->assertSame(2, $guide['inputLines']);
        $this->assertSame(4, $guide['characters']);
        $this->assertSame(1, $guide['wrappedLines']);
        $this->assertSame(1, $guide['qrCodes']);
    }
}
