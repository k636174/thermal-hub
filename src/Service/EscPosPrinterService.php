<?php
declare(strict_types=1);

namespace App\Service;

use RuntimeException;

class EscPosPrinterService
{
    /** Send text to a network ESC/POS printer. */
    public function print(string $host, int $port, string $body, string $encoding, int $timeout): void
    {
        $payload = $this->buildPayload($body, $encoding);
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
        $converted = iconv('UTF-8', $encoding . '//TRANSLIT', str_replace(["\r\n", "\r"], "\n", $body));
        if ($converted === false) {
            throw new RuntimeException('本文をプリンター文字コードへ変換できません。');
        }

        return "\x1b\x40" . $converted . "\n\x1b\x64\x03\x1d\x56\x00";
    }
}
