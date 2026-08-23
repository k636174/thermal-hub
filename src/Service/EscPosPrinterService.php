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
        $linesPerPage = max(1, (int)floor($targetDots / self::DOTS_PER_LINE));
        $lines = $this->wrapLines($normalized);
        $payload = '';
        foreach (array_chunk($lines, $linesPerPage) as $pageLines) {
            $converted = $this->formatBody(implode("\n", $pageLines), $encoding);
            $remainingDots = $targetDots - (count($pageLines) * self::DOTS_PER_LINE);
            $payload .= "\x1b\x40" . $textPrefix . $converted . $textSuffix . "\n"
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

    /** @return list<string> */
    private function wrapLines(string $body): array
    {
        $wrapped = [];
        foreach (explode("\n", $body) as $line) {
            if ($line === '') {
                $wrapped[] = '';
                continue;
            }
            // Keep markup intact; splitting inside a marker would print the marker literally.
            if (str_contains($line, '!!') || str_contains($line, '[[QR:')) {
                $wrapped[] = $line;
                continue;
            }
            $current = '';
            $width = 0;
            foreach (mb_str_split($line) as $character) {
                $characterWidth = mb_strwidth($character, 'UTF-8');
                if ($current !== '' && $width + $characterWidth > self::COLUMNS_PER_LINE) {
                    $wrapped[] = $current;
                    $current = '';
                    $width = 0;
                }
                $current .= $character;
                $width += $characterWidth;
            }
            $wrapped[] = $current;
        }

        return $wrapped;
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
