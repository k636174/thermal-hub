<?php
declare(strict_types=1);

namespace App\Service;

use RuntimeException;

class ImagePrintService
{
    /** @param array<string, mixed> $printer */
    public function print(string $path, array $printer): void
    {
        if (empty($printer['raster_enabled'])) {
            throw new RuntimeException('このプリンターでは画像印字が無効です。');
        }
        $bytes = file_get_contents($path);
        if ($bytes === false) {
            throw new RuntimeException('保存画像を読み込めません。');
        }
        $payload = $this->buildPayload($bytes, $printer);
        (new EscPosPrinterService())->sendPayload(
            (string)$printer['host'],
            (int)$printer['port'],
            $payload,
            (int)$printer['timeout'],
        );
    }

    /** @param array<string, mixed> $printer */
    public function buildPayload(string $imageBytes, array $printer): string
    {
        $rendered = (new RasterImageService())->renderUploaded(
            $imageBytes,
            (int)$printer['printable_width_dots'],
            (int)floor((float)$printer['label_length_mm'] * (int)$printer['dpi'] / 25.4),
        );

        return "\x1b\x40\x1b\x33\x00\x1b\x61\x01" . $rendered['escpos']
            . "\x1b\x64\x03\x1d\x56\x00";
    }
}
