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
        $converted = iconv('UTF-8', $encoding . '//TRANSLIT', $normalized);
        if ($converted === false) {
            throw new RuntimeException('本文をプリンター文字コードへ変換できません。');
        }

        $textPrefix = '';
        $textSuffix = '';
        if (in_array(strtoupper($encoding), ['CP932', 'SHIFT_JIS'], true)) {
            // FS C 1 selects Shift_JIS, and FS & enables two-byte Kanji mode.
            $textPrefix = "\x1c\x43\x01\x1c\x26";
            $textSuffix = "\x1c\x2e";
        }

        $feed = "\x1b\x64\x03";
        $millimeters = $lengths[$paperLength];
        if ($millimeters !== null) {
            $lineCount = substr_count($normalized, "\n") + 1;
            $targetDots = (int)round($millimeters * self::DOTS_PER_INCH / 25.4);
            $remainingDots = $targetDots - ($lineCount * self::DOTS_PER_LINE);
            if ($remainingDots > 0) {
                $feed = $this->feedDots($remainingDots);
            }
        }

        return "\x1b\x40" . $textPrefix . $converted . $textSuffix . "\n" . $feed . "\x1d\x56\x00";
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
