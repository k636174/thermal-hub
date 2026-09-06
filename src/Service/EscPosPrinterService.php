<?php
declare(strict_types=1);

namespace App\Service;

use RuntimeException;

class EscPosPrinterService
{
    /** Send text to a network ESC/POS printer. */
    public function print(
        string $host,
        int $port,
        string $body,
        string $encoding,
        int $timeout,
    ): void {
        $payload = $this->buildPayload($body, $encoding);
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
    public function buildPayload(string $body, string $encoding): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $body);
        $textPrefix = '';
        $textSuffix = '';
        if (in_array(strtoupper($encoding), ['CP932', 'SHIFT_JIS'], true)) {
            // FS C 1 selects Shift_JIS, and FS & enables two-byte Kanji mode.
            $textPrefix = "\x1c\x43\x01\x1c\x26";
            $textSuffix = "\x1c\x2e";
        }

        $converted = $this->formatBody($normalized, $encoding);

        return "\x1b\x40" . $textPrefix . $converted . $textSuffix
            . "\n\x1b\x64\x03\x1d\x56\x00";
    }

    /**
     * Build a payload from trusted style flags without parsing control markup in user text.
     *
     * @param list<array{text: string, reversed: bool, feedDots?: int}> $segments
     */
    public function buildSegmentedPayload(
        array $segments,
        string $encoding,
        ?int $lineSpacingDots = null,
        int $extraFeedDots = 0,
        int $finalFeedLines = 3,
    ): string {
        if (
            ($lineSpacingDots !== null && ($lineSpacingDots < 0 || $lineSpacingDots > 255))
            || $extraFeedDots < 0 || $extraFeedDots > 255
            || $finalFeedLines < 0 || $finalFeedLines > 255
        ) {
            throw new RuntimeException('行間隔または紙送り量の指定が不正です。');
        }
        $textPrefix = '';
        $textSuffix = '';
        if (in_array(strtoupper($encoding), ['CP932', 'SHIFT_JIS'], true)) {
            $textPrefix = "\x1c\x43\x01\x1c\x26";
            $textSuffix = "\x1c\x2e";
        }

        $formatted = '';
        foreach ($segments as $segment) {
            $text = str_replace(["\r\n", "\r"], "\n", $segment['text']);
            $converted = $this->convert($text, $encoding);
            $formatted .= $segment['reversed']
                ? "\x1d\x42\x01" . $converted . "\x1d\x42\x00"
                : $converted;
            $feedDots = $segment['feedDots'] ?? 0;
            if ($feedDots < 0 || $feedDots > 255) {
                throw new RuntimeException('セグメントの紙送り量が不正です。');
            }
            if ($feedDots > 0) {
                $formatted .= "\x1b\x4a" . chr($feedDots);
            }
        }

        $lineSpacing = $lineSpacingDots === null ? '' : "\x1b\x33" . chr($lineSpacingDots);
        $extraFeed = $extraFeedDots > 0 ? "\x1b\x4a" . chr($extraFeedDots) : '';
        $finalFeed = $finalFeedLines > 0 ? "\n\x1b\x64" . chr($finalFeedLines) : '';

        return "\x1b\x40" . $lineSpacing . $textPrefix . $formatted . $textSuffix
            . $finalFeed . $extraFeed . "\x1d\x56\x00";
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
}
