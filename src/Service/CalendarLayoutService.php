<?php
declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;
use InvalidArgumentException;

class CalendarLayoutService
{
    /** Render a horizontal six-week monthly calendar as SVG. */
    public function renderSvg(
        int $year,
        int $month,
        int $dpi,
        int $printableWidthDots,
        float $labelLengthMm,
    ): string {
        $this->validate($year, $month, $dpi, $printableWidthDots, $labelLengthMm);
        $canvasWidth = (int)round($labelLengthMm * $dpi / 25.4);
        $canvasHeight = $printableWidthDots;
        $margin = max(8, (int)round($dpi * 2 / 25.4));
        $headerHeight = max(42, (int)round($canvasHeight * 0.12));
        $weekdayHeight = max(25, (int)round($canvasHeight * 0.07));
        $gridTop = $margin + $headerHeight + $weekdayHeight;
        $gridBottom = $canvasHeight - $margin;
        $gridWidth = $canvasWidth - ($margin * 2);
        $columnWidth = $gridWidth / 7;
        $rowHeight = ($gridBottom - $gridTop) / 6;
        $titleSize = max(18, (int)round($headerHeight * 0.48));
        $weekdaySize = max(11, (int)round($weekdayHeight * 0.48));
        $daySize = max(11, (int)round(min($columnWidth, $rowHeight) * 0.24));

        $elements = [
            sprintf(
                '<text x="%d" y="%d" font-size="%d" font-weight="bold">%d年%d月</text>',
                $margin,
                $margin + (int)round($headerHeight * 0.68),
                $titleSize,
                $year,
                $month,
            ),
        ];
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        foreach ($weekdays as $column => $weekday) {
            $x = $margin + (($column + 0.5) * $columnWidth);
            $y = $margin + $headerHeight + (int)round($weekdayHeight * 0.68);
            $elements[] = sprintf(
                '<text x="%.1f" y="%d" font-size="%d" text-anchor="middle" font-weight="bold">%s</text>',
                $x,
                $y,
                $weekdaySize,
                $weekday,
            );
        }

        for ($column = 0; $column <= 7; $column++) {
            $x = $margin + ($column * $columnWidth);
            $elements[] = sprintf(
                '<line x1="%.1f" y1="%d" x2="%.1f" y2="%.1f"/>',
                $x,
                $gridTop,
                $x,
                $gridBottom,
            );
        }
        for ($row = 0; $row <= 6; $row++) {
            $y = $gridTop + ($row * $rowHeight);
            $elements[] = sprintf(
                '<line x1="%d" y1="%.1f" x2="%d" y2="%.1f"/>',
                $margin,
                $y,
                $canvasWidth - $margin,
                $y,
            );
        }

        $monthStart = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $calendarStart = $monthStart->modify('-' . $monthStart->format('w') . ' days');
        for ($index = 0; $index < 42; $index++) {
            $date = $calendarStart->modify('+' . $index . ' days');
            $column = $index % 7;
            $row = intdiv($index, 7);
            $x = $margin + ($column * $columnWidth) + max(4, $columnWidth * 0.08);
            $y = $gridTop + ($row * $rowHeight) + $daySize + max(2, $rowHeight * 0.05);
            $outsideMonth = $date->format('Y-m') !== $monthStart->format('Y-m');
            $elements[] = sprintf(
                '<text data-date="%s" x="%.1f" y="%.1f" font-size="%d"%s>%s</text>',
                $date->format('Y-m-d'),
                $x,
                $y,
                $daySize,
                $outsideMonth ? ' fill="#999"' : '',
                $date->format('j'),
            );
        }

        $textElements = array_filter(
            $elements,
            static fn(string $element): bool => !str_starts_with($element, '<line'),
        );
        $lineElements = array_filter(
            $elements,
            static fn(string $element): bool => str_starts_with($element, '<line'),
        );

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">'
            . '<rect width="100%%" height="100%%" fill="white"/>'
            . '<g fill="black" stroke="none" font-family="Noto Sans CJK JP">%s</g>'
            . '<g fill="none" stroke="black" stroke-width="1">%s</g></svg>',
            $canvasWidth,
            $canvasHeight,
            $canvasWidth,
            $canvasHeight,
            implode('', $textElements),
            implode('', $lineElements),
        );
    }

    /** Validate printer and month dimensions before allocating an image. */
    private function validate(int $year, int $month, int $dpi, int $width, float $length): void
    {
        if ($year < 1900 || $year > 2100 || $month < 1 || $month > 12) {
            throw new InvalidArgumentException('年月の指定が不正です。');
        }
        if ($dpi !== 203 || $width < 128 || $width > 832 || $length < 20 || $length > 300) {
            throw new InvalidArgumentException('プリンターの印字寸法が不正です。');
        }
    }
}
