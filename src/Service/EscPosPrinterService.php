<?php
declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;
use RuntimeException;

class EscPosPrinterService
{
    public const PAPER_LENGTH_NONE = 'none';
    public const PAPER_LENGTH_NARROW = 'narrow';
    public const PAPER_LENGTH_M5 = 'm5';

    private const DOTS_PER_INCH = 203;
    private const DOTS_PER_LINE = 30;
    private const COLUMNS_PER_LINE = 48;
    private const QR_MODULE_SIZE = 6;
    /** Correct the printer's measured 146mm feed to the requested 170mm. */
    private const PAPER_FEED_CALIBRATION = 170 / 146;

    /** Send text to a network ESC/POS printer. */
    public function print(
        string $host,
        int $port,
        string $body,
        string $encoding,
        int $timeout,
        string $paperLength = self::PAPER_LENGTH_NONE,
    ): void {
        $payload = $this->buildPayload($body, $encoding, $paperLength);
        $this->sendPayload($host, $port, $payload, $timeout);
    }

    /** Send a prepared ESC/POS payload. */
    public function sendPayload(string $host, int $port, string $payload, int $timeout): void
    {
        $errorMessage = '';
        set_error_handler(static function (int $severity, string $message) use (&$errorMessage): bool {
            $errorMessage = $message;

            return true;
        });
        try {
            $socket = stream_socket_client("tcp://{$host}:{$port}", $errorCode, $errorMessage, $timeout);
        } finally {
            restore_error_handler();
        }
        if ($socket === false) {
            throw new RuntimeException("プリンターに接続できません ({$errorCode}): {$errorMessage}");
        }
        stream_set_timeout($socket, $timeout);
        $written = 0;
        $payloadLength = strlen($payload);
        while ($written < $payloadLength) {
            $result = fwrite($socket, substr($payload, $written));
            if ($result === false || $result === 0) {
                fclose($socket);
                throw new RuntimeException('印字データの送信に失敗しました。');
            }
            $written += $result;
        }
        fclose($socket);
    }

    /** Build the ESC/POS byte payload. */
    public function buildPayload(
        string $body,
        string $encoding,
        string $paperLength = self::PAPER_LENGTH_NONE,
    ): string {
        $lengths = [
            self::PAPER_LENGTH_NONE => null,
            self::PAPER_LENGTH_NARROW => 170,
            self::PAPER_LENGTH_M5 => 105,
        ];
        if (!array_key_exists($paperLength, $lengths)) {
            throw new InvalidArgumentException('用紙長の指定が不正です。');
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $body);
        $textPrefix = '';
        $textSuffix = '';
        if (in_array(strtoupper($encoding), ['CP932', 'SHIFT_JIS'], true)) {
            // FS C 1 selects Shift_JIS, and FS & enables two-byte Kanji mode.
            $textPrefix = "\x1c\x43\x01\x1c\x26";
            $textSuffix = "\x1c\x2e";
        }

        $millimeters = $lengths[$paperLength];
        if ($millimeters === null) {
            $converted = $this->formatBody($normalized, $encoding);

            return "\x1b\x40" . $textPrefix . $converted . $textSuffix
                . "\n\x1b\x64\x03\x1d\x56\x00";
        }

        $targetDots = (int)round(
            $millimeters * self::DOTS_PER_INCH / 25.4 * self::PAPER_FEED_CALIBRATION,
        );
        $units = $this->layoutUnits($normalized);
        $payload = '';
        foreach ($this->paginate($units, $targetDots) as $pageUnits) {
            $converted = '';
            $usedDots = 0;
            foreach ($pageUnits as $unit) {
                $converted .= $this->formatBody($unit['body'], $encoding);
                if ($unit['type'] === 'text') {
                    $converted .= "\n";
                }
                $usedDots += $unit['dots'];
            }
            $remainingDots = max(0, $targetDots - $usedDots);
            $payload .= "\x1b\x40" . $textPrefix . $converted . $textSuffix
                . $this->feedDots($remainingDots) . "\x1d\x56\x00";
        }

        return $payload;
    }

    /** Convert UTF-8 text to the printer encoding. */
    private function convert(string $body, string $encoding): string
    {
        $converted = iconv('UTF-8', $encoding . '//TRANSLIT', $body);
        if ($converted === false) {
            throw new RuntimeException('本文をプリンター文字コードへ変換できません。');
        }

        return $converted;
    }

    /** Convert supported inline markup to ESC/POS commands. */
    private function formatBody(string $body, string $encoding): string
    {
        $parts = preg_split('/(\[\[QR:.+?\]\]|!!.+?!!)/su', $body, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            throw new RuntimeException('印字記法を解析できません。');
        }

        $formatted = '';
        foreach ($parts as $part) {
            if (preg_match('/^!!(.+?)!!$/su', $part, $match) === 1) {
                $formatted .= "\x1d\x42\x01" . $this->convert($match[1], $encoding) . "\x1d\x42\x00";
                continue;
            }
            if (preg_match('/^\[\[QR:(.+?)\]\]$/su', $part, $match) === 1) {
                $formatted .= $this->qrCode($match[1]);
                continue;
            }
            $formatted .= $this->convert($part, $encoding);
        }

        return $formatted;
    }

    /** Build ESC/POS model 2 QR-code commands using UTF-8 data. */
    private function qrCode(string $data): string
    {
        $storeLength = strlen($data) + 3;

        return "\x1d\x28\x6b\x04\x00\x31\x41\x32\x00"
            . "\x1d\x28\x6b\x03\x00\x31\x43\x06"
            . "\x1d\x28\x6b\x03\x00\x31\x45\x31"
            . "\x1d\x28\x6b" . pack('v', $storeLength) . "\x31\x50\x30" . $data
            . "\x1d\x28\x6b\x03\x00\x31\x51\x30";
    }

    /** @return list<array{type: string, body: string, dots: int}> */
    private function layoutUnits(string $body): array
    {
        $units = [];
        foreach (explode("\n", $body) as $line) {
            $parts = preg_split('/(\[\[QR:.+?\]\]|!!.+?!!)/su', $line, -1, PREG_SPLIT_DELIM_CAPTURE);
            if ($parts === false) {
                throw new RuntimeException('印字記法を解析できません。');
            }

            /** @var list<array{character: string, reverse: bool}> $row */
            $row = [];
            $width = 0;
            $hasUnit = false;
            foreach ($parts as $part) {
                if (preg_match('/^\[\[QR:(.+?)\]\]$/su', $part, $match) === 1) {
                    $this->flushTextRow($units, $row, $width);
                    $units[] = [
                        'type' => 'qr',
                        'body' => $part,
                        'dots' => $this->qrHeightDots($match[1]),
                    ];
                    $hasUnit = true;
                    continue;
                }
                $reverse = preg_match('/^!!(.+?)!!$/su', $part, $match) === 1;
                $text = $reverse ? $match[1] : $part;
                foreach (mb_str_split($text) as $character) {
                    $characterWidth = mb_strwidth($character, 'UTF-8');
                    if ($row !== [] && $width + $characterWidth > self::COLUMNS_PER_LINE) {
                        $this->flushTextRow($units, $row, $width);
                        $hasUnit = true;
                    }
                    $row[] = ['character' => $character, 'reverse' => $reverse];
                    $width += $characterWidth;
                }
            }
            if ($row !== []) {
                $this->flushTextRow($units, $row, $width);
            } elseif (!$hasUnit) {
                $units[] = ['type' => 'text', 'body' => '', 'dots' => self::DOTS_PER_LINE];
            }
        }

        return $units;
    }

    /**
     * @param list<array{type: string, body: string, dots: int}> $units
     * @param list<array{character: string, reverse: bool}> $row
     */
    private function flushTextRow(array &$units, array &$row, int &$width): void
    {
        if ($row === []) {
            return;
        }

        $body = '';
        $reverse = false;
        foreach ($row as $item) {
            if ($item['reverse'] !== $reverse) {
                $body .= '!!';
                $reverse = $item['reverse'];
            }
            $body .= $item['character'];
        }
        if ($reverse) {
            $body .= '!!';
        }
        $units[] = ['type' => 'text', 'body' => $body, 'dots' => self::DOTS_PER_LINE];
        $row = [];
        $width = 0;
    }

    /** Calculate the printed QR height selected automatically by the printer. */
    private function qrHeightDots(string $data): int
    {
        // ISO/IEC 18004 byte-mode capacities for error correction level M.
        $capacities = [
            14, 26, 42, 62, 84, 106, 122, 152, 180, 213,
            251, 287, 331, 362, 412, 450, 504, 560, 624, 666,
            711, 779, 857, 911, 997, 1059, 1125, 1190, 1264, 1370,
            1452, 1538, 1628, 1722, 1809, 1911, 1989, 2099, 2213, 2331,
        ];
        $version = 40;
        foreach ($capacities as $index => $capacity) {
            if (strlen($data) <= $capacity) {
                $version = $index + 1;
                break;
            }
        }

        return (17 + (4 * $version)) * self::QR_MODULE_SIZE;
    }

    /**
     * @param list<array{type: string, body: string, dots: int}> $units
     * @return list<list<array{type: string, body: string, dots: int}>>
     */
    private function paginate(array $units, int $targetDots): array
    {
        $pages = [];
        $page = [];
        $usedDots = 0;
        foreach ($units as $unit) {
            if ($page !== [] && $usedDots + $unit['dots'] > $targetDots) {
                $pages[] = $page;
                $page = [];
                $usedDots = 0;
            }
            $page[] = $unit;
            $usedDots += $unit['dots'];
        }
        if ($page !== []) {
            $pages[] = $page;
        }

        return $pages;
    }

    /** Build one or more ESC J commands (maximum 255 dots per command). */
    private function feedDots(int $dots): string
    {
        $commands = '';
        while ($dots > 0) {
            $chunk = min($dots, 255);
            $commands .= "\x1b\x4a" . chr($chunk);
            $dots -= $chunk;
        }

        return $commands;
    }
}
