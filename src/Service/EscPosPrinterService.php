<?php
declare(strict_types=1);

namespace App\Service;

use RuntimeException;

class EscPosPrinterService
{
    public function print(string $host, int $port, string $body, string $encoding, int $timeout): void
    {
        $payload = $this->buildPayload($body, $encoding);
        $socket = @stream_socket_client("tcp://{$host}:{$port}", $errorCode, $errorMessage, $timeout);
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

    public function buildPayload(string $body, string $encoding): string
    {
        $converted = iconv('UTF-8', $encoding . '//TRANSLIT', str_replace(["\r\n", "\r"], "\n", $body));
        if ($converted === false) {
            throw new RuntimeException('本文をプリンター文字コードへ変換できません。');
        }

        return "\x1b\x40" . $converted . "\n\x1b\x64\x03\x1d\x56\x00";
    }
}
