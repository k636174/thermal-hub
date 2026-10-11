<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\Date;
use DateTimeInterface;
use RuntimeException;

class TodoPrintService
{
    public const PAPER_GUIDES = [
        'narrow' => 'システム手帳 ナロ（170mm）',
        'm5' => 'M5／マイクロ5（105mm）',
    ];

    private const LIMITS = ['narrow' => 12, 'm5' => 7];
    private const LINE_SPACING_DOTS = 24;
    private const CUT_MARGIN_MM = 20.0;
    private const TEXT_COLUMNS = 48;

    /** @param list<array<string, mixed>> $todos @param array<string, mixed> $printer */
    public function print(array $todos, string $paperGuide, array $printer): void
    {
        $service = new EscPosPrinterService();
        $payload = $this->buildPayload(
            $todos,
            $paperGuide,
            (string)$printer['encoding'],
            (int)$printer['dpi'],
            $service,
        );
        $service->sendPayload(
            (string)$printer['host'],
            (int)$printer['port'],
            $payload,
            (int)$printer['timeout'],
        );
    }

    /** @param list<array<string, mixed>> $todos */
    public function buildPayload(
        array $todos,
        string $paperGuide,
        string $encoding,
        int $dpi = 203,
        ?EscPosPrinterService $printerService = null,
    ): string {
        if (!isset(self::LIMITS[$paperGuide])) {
            throw new RuntimeException('用紙サイズの指定が不正です。');
        }
        if (!$todos) {
            throw new RuntimeException('印字するTodoを選択してください。');
        }
        if (count($todos) > self::LIMITS[$paperGuide]) {
            throw new RuntimeException(self::PAPER_GUIDES[$paperGuide] . 'の印字上限を超えています。');
        }

        $segments = [['text' => "TODO\n", 'reversed' => true]];
        foreach ($todos as $todo) {
            $dueDate = $todo['due_date'] ?? null;
            $due = $dueDate instanceof DateTimeInterface || $dueDate instanceof Date
                ? ' ' . $dueDate->format('m/d')
                : '';
            $prefix = !empty($todo['is_completed']) ? '[x] ' : '[ ] ';
            $available = self::TEXT_COLUMNS - mb_strwidth($prefix . $due);
            $title = $this->truncate((string)$todo['title'], $available);
            $segments[] = ['text' => $prefix . $title . $due . "\n", 'reversed' => false];
        }
        $cutMarginDots = (int)round(self::CUT_MARGIN_MM * $dpi / 25.4);

        return ($printerService ?? new EscPosPrinterService())->buildSegmentedPayload(
            $segments,
            $encoding,
            self::LINE_SPACING_DOTS,
            $cutMarginDots,
            0,
        );
    }

    /** Return the maximum number of Todo rows for a planner size. */
    public function limit(string $paperGuide): int
    {
        return self::LIMITS[$paperGuide] ?? 0;
    }

    /** Shorten a title to the printer's display-column width. */
    private function truncate(string $value, int $columns): string
    {
        if (mb_strwidth($value) <= $columns) {
            return $value;
        }
        $result = '';
        foreach (mb_str_split($value) as $character) {
            if (mb_strwidth($result . $character . '…') > $columns) {
                break;
            }
            $result .= $character;
        }

        return $result . '…';
    }
}
