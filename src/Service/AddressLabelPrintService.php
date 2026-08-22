<?php
declare(strict_types=1);

namespace App\Service;

use RuntimeException;

class AddressLabelPrintService
{
    /** @param array<string, mixed> $label @param array<string, mixed> $printer */
    public function print(array $label, array $printer): void
    {
        if (empty($printer['raster_enabled'])) {
            throw new RuntimeException('このプリンターでは画像印字が無効です。');
        }
        $layout = new AddressLabelLayoutService();
        $svg = $layout->renderSvg(
            $label,
            (int)$printer['dpi'],
            (float)$printer['label_width_mm'],
            (float)$printer['label_length_mm'],
        );
        $rendered = (new RasterImageService())->render(
            $svg,
            (int)$printer['printable_width_dots'],
            $layout->feedTopOffsetDots((int)$printer['dpi']),
        );
        $payload = "\x1b\x40\x1b\x33\x00\x1b\x61\x01" . $rendered['escpos'] . "\n\x1d\x56\x00";
        (new EscPosPrinterService())->sendPayload(
            (string)$printer['host'],
            (int)$printer['port'],
            $payload,
            (int)$printer['timeout'],
        );
    }
}
