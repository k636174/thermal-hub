<?php
declare(strict_types=1);

namespace App\Service;

use Imagick;
use ImagickException;
use RuntimeException;

class RasterImageService
{
    private const MAX_PAYLOAD_BYTES = 2_000_000;

    /** Convert an uploaded raster image to a width-fitted ESC/POS bitmap. */
    public function renderUploaded(string $imageBytes, int $printableWidthDots): array
    {
        if (!class_exists('Imagick')) {
            throw new RuntimeException('画像印字にはPHP Imagick拡張が必要です。');
        }
        if ($printableWidthDots < 1 || $printableWidthDots > 65535) {
            throw new RuntimeException('印字可能幅が不正です。');
        }
        try {
            $image = new Imagick();
            $image->readImageBlob($imageBytes);
            $image->setIteratorIndex(0);
            $image->autoOrient();
            $image->setImageBackgroundColor('white');
            $image = $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            if ($image->getImageWidth() > $printableWidthDots) {
                $image->thumbnailImage($printableWidthDots, 0);
            }
            $image->setImageType(Imagick::IMGTYPE_GRAYSCALE);
            $image->thresholdImage(0.65 * Imagick::getQuantum());
            $width = $image->getImageWidth();
            $height = $image->getImageHeight();
            $pixels = [];
            for ($y = 0; $y < $height; $y++) {
                $row = [];
                foreach ($image->exportImagePixels(0, $y, $width, 1, 'I', Imagick::PIXEL_CHAR) as $intensity) {
                    $row[] = (int)$intensity < 128;
                }
                $pixels[] = $row;
            }
            $escpos = $this->bitmapToEscPos($pixels);
            if (strlen($escpos) > self::MAX_PAYLOAD_BYTES) {
                throw new RuntimeException('生成した印字データが上限を超えています。');
            }
            $image->setImageFormat('png');
            $png = $image->getImagesBlob();
            $image->clear();
        } catch (ImagickException) {
            throw new RuntimeException('画像データを読み込めません。');
        }

        return compact('png', 'escpos', 'width', 'height');
    }

    /** @return array{png: string, escpos: string, width: int, height: int} */
    public function render(string $svg, int $printableWidthDots): array
    {
        if (!class_exists('Imagick')) {
            throw new RuntimeException('画像印字にはPHP Imagick拡張が必要です。');
        }

        /** @var \Imagick $image */
        $image = new Imagick();
        $image->setBackgroundColor('white');
        $image->readImageBlob($svg);
        $image->setImageFormat('png');
        $image->setImageBackgroundColor('white');
        $image->rotateImage('white', 90);
        $image->setImageType(Imagick::IMGTYPE_GRAYSCALE);
        $image->thresholdImage(0.65 * Imagick::getQuantum());
        $width = $image->getImageWidth();
        $height = $image->getImageHeight();
        if ($width > $printableWidthDots) {
            throw new RuntimeException("回転後の画像幅{$width}dotが印字可能幅{$printableWidthDots}dotを超えています。");
        }

        $pixels = [];
        for ($y = 0; $y < $height; $y++) {
            $row = [];
            foreach ($image->exportImagePixels(0, $y, $width, 1, 'I', Imagick::PIXEL_CHAR) as $intensity) {
                $row[] = (int)$intensity < 128;
            }
            $pixels[] = $row;
        }
        $escpos = $this->bitmapToEscPos($pixels);
        if (strlen($escpos) > self::MAX_PAYLOAD_BYTES) {
            throw new RuntimeException('生成した印字データが上限を超えています。');
        }

        $png = $image->getImagesBlob();
        $image->clear();

        return compact('png', 'escpos', 'width', 'height');
    }

    /** Rotate the exact print bitmap back to the label's viewing orientation. */
    public function orientForPreview(string $rotatedPng, ?int $visiblePaperWidthDots = null): string
    {
        if (!class_exists('Imagick')) {
            throw new RuntimeException('画像プレビューにはPHP Imagick拡張が必要です。');
        }
        $image = new Imagick();
        $image->setBackgroundColor('white');
        $image->readImageBlob($rotatedPng);
        $image->rotateImage('white', -90);
        if ($visiblePaperWidthDots !== null) {
            $height = $image->getImageHeight();
            if ($visiblePaperWidthDots <= 0 || $visiblePaperWidthDots > $height) {
                throw new RuntimeException('プレビュー用紙幅の指定が不正です。');
            }
            $image->cropImage(
                $image->getImageWidth(),
                $visiblePaperWidthDots,
                0,
                $height - $visiblePaperWidthDots,
            );
            $image->setImagePage(0, 0, 0, 0);
        }
        $image->setImageFormat('png');
        $preview = $image->getImagesBlob();
        $image->clear();

        return $preview;
    }

    /** @param list<list<bool>> $pixels */
    public function bitmapToEscPos(array $pixels): string
    {
        $height = count($pixels);
        $width = $height > 0 ? count($pixels[0]) : 0;
        if ($height === 0 || $width === 0 || $width > 65535 || $height > 65535) {
            throw new RuntimeException('画像サイズが不正です。');
        }
        $bytesPerRow = (int)ceil($width / 8);
        $data = '';
        foreach ($pixels as $row) {
            if (count($row) !== $width) {
                throw new RuntimeException('画像の行幅が一致しません。');
            }
            for ($byteIndex = 0; $byteIndex < $bytesPerRow; $byteIndex++) {
                $byte = 0;
                for ($bit = 0; $bit < 8; $bit++) {
                    $x = ($byteIndex * 8) + $bit;
                    if ($x < $width && $row[$x]) {
                        $byte |= 1 << 7 - $bit;
                    }
                }
                $data .= chr($byte);
            }
        }

        return "\x1d\x76\x30\x00"
            . chr($bytesPerRow & 0xff) . chr(($bytesPerRow >> 8) & 0xff)
            . chr($height & 0xff) . chr(($height >> 8) & 0xff) . $data;
    }
}
