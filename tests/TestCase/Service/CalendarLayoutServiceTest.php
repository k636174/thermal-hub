<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\CalendarLayoutService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CalendarLayoutServiceTest extends TestCase
{
    public function testRenderSvgBuildsHorizontalSixWeekCalendar(): void
    {
        $svg = (new CalendarLayoutService())->renderSvg(2026, 9, 203, 576, 100.0);

        $this->assertStringContainsString('width="799" height="576"', $svg);
        $this->assertStringContainsString('2026年9月', $svg);
        $this->assertStringContainsString('data-date="2026-08-30"', $svg);
        $this->assertStringContainsString('data-date="2026-10-10"', $svg);
        $this->assertSame(42, substr_count($svg, 'data-date='));
    }

    public function testRenderSvgRejectsInvalidMonth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CalendarLayoutService())->renderSvg(2026, 13, 203, 576, 100.0);
    }
}
