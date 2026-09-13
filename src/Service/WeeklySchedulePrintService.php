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
    private const NOTE_INDENT = '　｜';
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
            $noteSegments = $this->noteSegments($notes[$index] ?? '');
            $segments[] = ['text' => "\n", 'reversed' => false];
            array_push($segments, ...$noteSegments);
            $lastSegment = array_key_last($segments);
            $segments[$lastSegment]['feedDots'] = $dayHeightDots
                - ((1 + self::NOTE_LINES_PER_DAY) * self::LINE_SPACING_DOTS);
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

    /**
     * Build fixed-height note segments, interpreting !!text!! as reverse-print markup.
     *
     * @return list<array{text: string, reversed: bool}>
     */
    private function noteSegments(string $note): array
    {
        $parts = preg_split('/(!!.+?!!)/su', $note, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$note];
        $lines = [[]];
        $lineWidth = 0;

        foreach ($parts as $part) {
            $reversed = preg_match('/^!!(.+?)!!$/su', $part, $match) === 1;
            $text = $reversed ? $match[1] : $part;
            foreach (mb_str_split(str_replace(["\r\n", "\r"], "\n", $text)) as $character) {
                if ($character === "\n") {
                    $lines[] = [];
                    $lineWidth = 0;
                    continue;
                }
                $characterWidth = mb_strwidth($character);
                if ($lineWidth > 0 && $lineWidth + $characterWidth > self::NOTE_TEXT_COLUMNS) {
                    $lines[] = [];
                    $lineWidth = 0;
                }
                $lines[array_key_last($lines)][] = ['text' => $character, 'reversed' => $reversed];
                $lineWidth += $characterWidth;
            }
        }

        $lines = array_slice(array_pad($lines, self::NOTE_LINES_PER_DAY, []), 0, self::NOTE_LINES_PER_DAY);
        $segments = [];
        foreach ($lines as $line) {
            $this->appendSegment($segments, self::NOTE_INDENT, false);
            foreach ($line as $character) {
                $this->appendSegment($segments, $character['text'], $character['reversed']);
            }
            $this->appendSegment($segments, "\n", false);
        }

        return $segments;
    }

    /** @param list<array{text: string, reversed: bool}> $segments */
    private function appendSegment(array &$segments, string $text, bool $reversed): void
    {
        $last = array_key_last($segments);
        if ($last !== null && $segments[$last]['reversed'] === $reversed) {
            $segments[$last]['text'] .= $text;

            return;
        }
        $segments[] = ['text' => $text, 'reversed' => $reversed];
    }
}
