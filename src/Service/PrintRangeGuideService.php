<?php
declare(strict_types=1);

namespace App\Service;

class PrintRangeGuideService
{
    public const COLUMNS_PER_LINE = 48;
    public const NARROW_LINE_LIMIT = 52;
    public const M5_LINE_LIMIT = 32;

    /**
     * @return array{inputLines: int, characters: int, maxColumns: int, wrappedLines: int, qrCodes: int}
     */
    public function analyze(string $body): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $body);
        $lines = explode("\n", $normalized);
        $characters = 0;
        $maxColumns = 0;
        $wrappedLines = 0;
        $qrCodes = 0;

        foreach ($lines as $line) {
            $withoutQr = preg_replace_callback(
                '/\[\[QR:(.+?)\]\]/su',
                static function (array $match) use (&$qrCodes): string {
                    $qrCodes++;

                    return '';
                },
                $line,
            );
            $visible = preg_replace('/!!(.+?)!!/su', '$1', $withoutQr ?? $line) ?? $line;
            $lineCharacters = mb_strlen($visible, 'UTF-8');
            $columns = mb_strwidth($visible, 'UTF-8');
            $characters += $lineCharacters;
            $maxColumns = max($maxColumns, $columns);

            if ($visible !== '') {
                $wrappedLines += max(1, (int)ceil($columns / self::COLUMNS_PER_LINE));
            } elseif ($line === '') {
                $wrappedLines++;
            }
        }

        return [
            'inputLines' => count($lines),
            'characters' => $characters,
            'maxColumns' => $maxColumns,
            'wrappedLines' => $wrappedLines,
            'qrCodes' => $qrCodes,
        ];
    }
}
