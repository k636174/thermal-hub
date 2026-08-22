<?php
declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;

class AddressLabelLayoutService
{
    private const MARGIN_MM = 3.0;
    private const POSTAL_TOP_OFFSET_MM = 4.0;
    private const ADDRESS_START_RATIO = 0.27;
    private const RECIPIENT_START_RATIO = 0.60;
    private const MIN_ADDRESS_PT = 12;
    private const MIN_RECIPIENT_PT = 18;

    /**
     * Render a horizontal address label as SVG. The raster service rotates it for printing.
     *
     * @param array<string, mixed> $label
     */
    public function renderSvg(array $label, int $dpi, float $labelWidthMm, float $labelLengthMm): string
    {
        $this->validateDimensions($dpi, $labelWidthMm, $labelLengthMm);
        $width = $this->mmToDots($labelLengthMm, $dpi);
        $height = $this->mmToDots($labelWidthMm, $dpi);
        $margin = $this->mmToDots(self::MARGIN_MM, $dpi);
        $contentWidth = $width - ($margin * 2);
        $postal = $this->postalCode((string)($label['postal_code'] ?? ''));
        $address = array_values(array_filter([
            $this->normalize((string)($label['address_line1'] ?? '')),
            $this->normalize((string)($label['address_line2'] ?? '')),
        ], static fn(string $value): bool => $value !== ''));
        $recipient = $this->normalize((string)($label['recipient_name'] ?? ''));
        if ($address === [] || $recipient === '') {
            throw new InvalidArgumentException('住所と宛名は必須です。');
        }
        $honorific = (string)($label['honorific'] ?? '様');
        if ($honorific !== 'なし') {
            $recipient .= ' ' . $honorific;
        }
        $postalSize = $this->pointsToDots(16, $dpi);
        $addressAreaHeight = (int)floor($height * 0.42);
        [$addressLines, $addressSize] = $this->fitText(
            implode(' ', $address),
            $contentWidth,
            $addressAreaHeight,
            $this->pointsToDots(18, $dpi),
            $this->pointsToDots(self::MIN_ADDRESS_PT, $dpi),
            3,
            '住所',
        );
        [$recipientLines, $recipientSize] = $this->fitText(
            $recipient,
            $contentWidth,
            (int)floor($height * 0.35),
            $this->pointsToDots(28, $dpi),
            $this->pointsToDots(self::MIN_RECIPIENT_PT, $dpi),
            2,
            '宛名',
        );

        $elements = [];
        if ($postal !== '') {
            $postalY = $margin + $this->mmToDots(self::POSTAL_TOP_OFFSET_MM, $dpi) + $postalSize;
            $elements[] = $this->textElement($margin, $postalY, $postal, $postalSize, 'start');
        }
        $addressY = (int)floor($height * self::ADDRESS_START_RATIO);
        foreach ($addressLines as $index => $line) {
            $y = $addressY + (($index + 1) * (int)round($addressSize * 1.25));
            $elements[] = $this->textElement($margin, $y, $line, $addressSize, 'start');
        }
        $recipientStartY = (int)floor($height * self::RECIPIENT_START_RATIO);
        foreach ($recipientLines as $index => $line) {
            $y = $recipientStartY + (($index + 1) * (int)round($recipientSize * 1.25));
            $elements[] = $this->textElement(
                (int)floor($width / 2),
                $y,
                $line,
                $recipientSize,
                'middle',
            );
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">'
            . '<rect width="100%%" height="100%%" fill="white"/>'
            . '<g fill="black" font-family="Noto Sans JP">%s</g></svg>',
            $width,
            $height,
            $width,
            $height,
            implode('', $elements),
        );
    }

    /** Convert millimeters to printer dots. */
    public function mmToDots(float $millimeters, int $dpi): int
    {
        return (int)round($millimeters * $dpi / 25.4);
    }

    /** Validate supported dimensions. */
    private function validateDimensions(int $dpi, float $width, float $length): void
    {
        if ($dpi !== 203 || $width < 20 || $width > 300 || $length < 20 || $length > 300) {
            throw new InvalidArgumentException('ラベル寸法の指定が不正です。');
        }
    }

    /** Format a postal code for display. */
    private function postalCode(string $value): string
    {
        $digits = preg_replace('/\D/u', '', mb_convert_kana($value, 'n')) ?? '';

        return strlen($digits) === 7 ? '〒' . substr($digits, 0, 3) . '-' . substr($digits, 3) : '';
    }

    /** Normalize user-entered single-line text. */
    private function normalize(string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    /** @return array{list<string>, int} */
    private function fitText(
        string $text,
        int $maxWidth,
        int $maxHeight,
        int $startSize,
        int $minSize,
        int $maxLines,
        string $field,
    ): array {
        for ($size = $startSize; $size >= $minSize; $size--) {
            $lines = $this->wrap($text, $maxWidth, $size);
            if (count($lines) <= $maxLines && count($lines) * $size * 1.25 <= $maxHeight) {
                return [$lines, $size];
            }
        }

        throw new InvalidArgumentException("{$field}がラベル内に収まりません。");
    }

    /** @return list<string> */
    private function wrap(string $text, int $maxWidth, int $fontSize): array
    {
        $lines = [];
        $current = '';
        $width = 0.0;
        foreach (mb_str_split($text) as $character) {
            $characterWidth = mb_strwidth($character, 'UTF-8') * $fontSize * 0.52;
            if ($current !== '' && $width + $characterWidth > $maxWidth) {
                $lines[] = trim($current);
                $current = '';
                $width = 0.0;
            }
            $current .= $character;
            $width += $characterWidth;
        }
        if ($current !== '' || $lines === []) {
            $lines[] = trim($current);
        }

        return $lines;
    }

    /** Convert typographic points to printer dots. */
    private function pointsToDots(int $points, int $dpi): int
    {
        return max(1, (int)round($points * $dpi / 72));
    }

    /** Build one escaped SVG text node. */
    private function textElement(int $x, int $y, string $text, int $size, string $anchor): string
    {
        return sprintf(
            '<text x="%d" y="%d" font-size="%d" text-anchor="%s">%s</text>',
            $x,
            $y,
            $size,
            $anchor,
            htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
        );
    }
}
