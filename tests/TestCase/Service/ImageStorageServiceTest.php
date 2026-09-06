<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ImageStorageService;
use Imagick;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ImageStorageServiceTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = TMP . 'tests' . DS . 'image-storage-' . bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->directory)) {
            foreach (glob($this->directory . DS . '*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($this->directory);
        }
        parent::tearDown();
    }

    public function testStoresValidImageOutsideWebRootWithGeneratedName(): void
    {
        if (!class_exists('Imagick')) {
            $this->markTestSkipped('Imagick is not installed.');
        }
        $image = new Imagick();
        $image->newImage(2, 2, 'white', 'png');
        $bytes = $image->getImagesBlob();
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $bytes);
        rewind($stream);
        $upload = new UploadedFile($stream, strlen($bytes), UPLOAD_ERR_OK, '../../sample.png', 'text/plain');

        $stored = (new ImageStorageService($this->directory))->store($upload);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}\.png$/', $stored['storage_name']);
        $this->assertSame('sample.png', $stored['original_name']);
        $this->assertSame('image/png', $stored['mime_type']);
        $this->assertFileExists($this->directory . DS . $stored['storage_name']);
    }

    public function testRejectsUnsupportedContentRegardlessOfClientMimeType(): void
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, 'not an image');
        rewind($stream);
        $upload = new UploadedFile($stream, 12, UPLOAD_ERR_OK, 'fake.png', 'image/png');

        $this->expectException(RuntimeException::class);
        (new ImageStorageService($this->directory))->store($upload);
    }

    public function testReportsConfiguredSizeLimitForPhpUploadRejection(): void
    {
        $upload = new UploadedFile('ignored', 0, UPLOAD_ERR_INI_SIZE, 'large.png', 'image/png');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('画像は10MB以下にしてください。');
        (new ImageStorageService($this->directory))->store($upload);
    }
}
