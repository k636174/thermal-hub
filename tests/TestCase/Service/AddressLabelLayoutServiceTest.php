<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\AddressLabelLayoutService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AddressLabelLayoutServiceTest extends TestCase
{
    public function testMmToDotsAt203Dpi(): void
    {
        $this->assertSame(575, (new AddressLabelLayoutService())->mmToDots(72.0, 203));
    }

    public function testRenderSvgUsesHorizontalCanvasAndEscapesInput(): void
    {
        $svg = (new AddressLabelLayoutService())->renderSvg([
            'postal_code' => '123-4567',
            'address_line1' => '東京都千代田区1-1',
            'address_line2' => '<ビル&101>',
            'recipient_name' => '山田 太郎',
            'honorific' => '様',
        ], 203, 72.0, 100.0);

        $this->assertStringContainsString('width="799" height="575"', $svg);
        $this->assertStringContainsString('font-family="Noto Sans JP"', $svg);
        $this->assertStringContainsString('〒123-4567', $svg);
        $this->assertStringContainsString('x="24" y="221" font-size="45"', $svg);
        $this->assertStringContainsString('x="24" y="307" font-size="51"', $svg);
        $this->assertStringContainsString('x="399" y="508" font-size="79"', $svg);
        $this->assertStringContainsString('&lt;ビル&amp;101&gt;', $svg);
        $this->assertStringContainsString('山田 太郎 様', $svg);
    }

    public function testRenderSvgRejectsMissingRecipient(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AddressLabelLayoutService())->renderSvg([
            'address_line1' => '東京都',
            'recipient_name' => '',
        ], 203, 72.0, 100.0);
    }
}
