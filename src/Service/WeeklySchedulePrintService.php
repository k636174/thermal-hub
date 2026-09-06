<?php
declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;

class WeeklySchedulePrintService
{
    private const CONTENT_LENGTH_MM = 150.0;
    private const CUT_MARGIN_MM = 20.0;
    private const NOTE_LINES_PER_DAY = 6;
    private const NOTE_TEXT_COLUMNS = 44;
    private const LINE_SPACING_DOTS = 24;
    private const DAY_SEPARATOR = ' ---------------------------';
    private const NOTE_INDENT = '　　';
    private const REVERSE_PADDING = '　';
    private const TRAILING_CALIBRATION_LINES = 3;

    /**
     * @param array<int, string> $notes
     * @param array<int, int> $invertedDays
     * @param array<string, mixed> $printer
     */
    public function print(DateTimeImmutable $weekStart, array $notes, array $invertedDays, array $printer): void
    {
        $printerService = new EscPosPrinterService();
        $payload = $this->buildPayload(
            $weekStart,
            $notes,
            $invertedDays,
            (string)$printer['encoding'],
            (int)$printer['dpi'],
            $printerService,
        );
        $printerService->sendPayload(
            (string)$printer['host'],
            (int)$printer['port'],
            $payload,
            (int)$printer['timeout'],
        );
    }

    /**
     * @param array<int, string> $notes
     * @param array<int, int> $invertedDays
     */
    public function buildPayload(
        DateTimeImmutable $weekStart,
        array $notes,
        array $invertedDays,
        string $encoding,
        int $dpi = 203,
        ?EscPosPrinterService $printerService = null,
    ): string {
        $weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $inverted = array_fill_keys(array_map('intval', $invertedDays), true);
        $segments = [];
        $contentDots = (int)round(self::CONTENT_LENGTH_MM * $dpi / 25.4);
        $cutMarginDots = (int)round(self::CUT_MARGIN_MM * $dpi / 25.4);
        $dayHeightDots = intdiv($contentDots, 7);
        for ($index = 0; $index < 7; $index++) {
            $date = $weekStart->modify('+' . $index . ' days');
            $isInverted = isset($inverted[$index]);
            $dateLabel = $date->format('Y/m/d') . ' (' . $weekdays[$index] . ')';
            $segments[] = [
                'text' => $isInverted
                    ? self::REVERSE_PADDING . $dateLabel . self::REVERSE_PADDING
                    : $dateLabel,
                'reversed' => $isInverted,
            ];
            $segments[] = [
                'text' => self::DAY_SEPARATOR,
                'reversed' => false,
            ];
            $noteLines = $this->noteLines($notes[$index] ?? '');
            $printedLines = 1 + count($noteLines);
            $segments[] = [
                'text' => "\n" . ($noteLines ? implode("\n", $noteLines) . "\n" : ''),
                'reversed' => false,
                'feedDots' => $dayHeightDots - ($printedLines * self::LINE_SPACING_DOTS),
            ];
        }
        $segments[] = [
            'text' => str_repeat(self::NOTE_INDENT . "\n", self::TRAILING_CALIBRATION_LINES),
            'reversed' => false,
        ];
        $extraFeedDots = $contentDots - ($dayHeightDots * 7) + $cutMarginDots;

        return ($printerService ?? new EscPosPrinterService())->buildSegmentedPayload(
            $segments,
            $encoding,
            self::LINE_SPACING_DOTS,
            $extraFeedDots,
            0,
        );
    }

    /** @return list<string> */
    private function noteLines(string $note): array
    {
        $lines = [];
        foreach (preg_split('/\R/u', $note) ?: [] as $sourceLine) {
            $current = '';
            foreach (mb_str_split($sourceLine) as $character) {
                if ($current !== '' && mb_strwidth($current . $character) > self::NOTE_TEXT_COLUMNS) {
                    $lines[] = self::NOTE_INDENT . $current;
                    $current = '';
                }
                $current .= $character;
            }
            $lines[] = self::NOTE_INDENT . $current;
        }

        return array_slice(
            array_pad($lines, self::NOTE_LINES_PER_DAY, self::NOTE_INDENT),
            0,
            self::NOTE_LINES_PER_DAY,
        );
    }
}
