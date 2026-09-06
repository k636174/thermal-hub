<?php
declare(strict_types=1);

namespace App\Service;

use finfo;
use Imagick;
use ImagickException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class ImageStorageService
{
    public const MAX_FILE_SIZE = 10_000_000;
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /** Use the private application storage directory unless one is explicitly supplied. */
    public function __construct(
        private readonly string $directory = ROOT . DS . 'storage' . DS . 'print-images',
    ) {
    }

    /** @return array{storage_name: string, original_name: string, mime_type: string, file_size: int} */
    public function store(UploadedFileInterface $upload): array
    {
        if ($upload->getError() !== UPLOAD_ERR_OK) {
            throw new RuntimeException('画像のアップロードに失敗しました。');
        }
        $size = $upload->getSize();
        if ($size === null || $size < 1 || $size > self::MAX_FILE_SIZE) {
            throw new RuntimeException('画像は10MB以下にしてください。');
        }
        $stream = $upload->getStream();
        $stream->rewind();
        $contents = $stream->getContents();
        if (strlen($contents) !== $size) {
            throw new RuntimeException('画像は10MB以下にしてください。');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($contents);
        if (!is_string($mime) || !isset(self::MIME_EXTENSIONS[$mime])) {
            throw new RuntimeException('JPEG、PNG、WebP画像を選択してください。');
        }
        if (!class_exists(Imagick::class)) {
            throw new RuntimeException('画像印字にはPHP Imagick拡張が必要です。');
        }
        try {
            $image = new Imagick();
            $image->pingImageBlob($contents);
            $width = $image->getImageWidth();
            $height = $image->getImageHeight();
            if ($image->getNumberImages() < 1 || $width < 1 || $height < 1) {
                throw new RuntimeException('画像データが破損しています。');
            }
            if ($width > 20_000 || $height > 20_000 || ($width * $height) > 40_000_000) {
                throw new RuntimeException('画像の縦横サイズが上限を超えています。');
            }
            $image->clear();
        } catch (ImagickException) {
            throw new RuntimeException('画像データを読み込めません。');
        }
        if (!is_dir($this->directory) && !mkdir($this->directory, 0770, true) && !is_dir($this->directory)) {
            throw new RuntimeException('画像保存領域を作成できません。');
        }
        $storageName = bin2hex(random_bytes(20)) . '.' . self::MIME_EXTENSIONS[$mime];
        $path = $this->path($storageName);
        if (file_put_contents($path, $contents, LOCK_EX) !== strlen($contents)) {
            throw new RuntimeException('画像を保存できません。');
        }
        chmod($path, 0660);
        $originalName = trim(basename(str_replace('\\', '/', $upload->getClientFilename() ?? 'image')));

        return [
            'storage_name' => $storageName,
            'original_name' => mb_substr($originalName !== '' ? $originalName : 'image', 0, 255),
            'mime_type' => $mime,
            'file_size' => $size,
        ];
    }

    /** Resolve a generated storage name without permitting directory traversal. */
    public function path(string $storageName): string
    {
        if (preg_match('/\A[a-f0-9]{40}\.(?:jpg|png|webp)\z/', $storageName) !== 1) {
            throw new RuntimeException('画像の保存名が不正です。');
        }

        return $this->directory . DIRECTORY_SEPARATOR . $storageName;
    }

    /** Delete one stored image if it is present. */
    public function delete(string $storageName): void
    {
        $path = $this->path($storageName);
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('保存画像を削除できません。');
        }
    }
}
