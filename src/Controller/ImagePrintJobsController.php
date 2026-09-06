<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\ImagePrintService;
use App\Service\ImageStorageService;
use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Psr\Http\Message\UploadedFileInterface;
use Throwable;

class ImagePrintJobsController extends AppController
{
    /** List the current user's uploaded images and raster printers. */
    public function index(): void
    {
        $uid = $this->currentUserId();
        $images = $this->fetchTable('ImagePrintJobs')->find()
            ->where(['user_id' => $uid])->orderByDesc('modified')->all();
        $printers = $this->fetchTable('Printers')->find('list')
            ->where(['user_id' => $uid, 'raster_enabled' => true])->toArray();
        $this->set(compact('images', 'printers'));
    }

    /** Validate and persist an uploaded image. */
    public function add(): Response
    {
        $this->getRequest()->allowMethod(['post']);
        $upload = $this->getRequest()->getData('image');
        if (!$upload instanceof UploadedFileInterface) {
            throw new BadRequestException('画像を選択してください。');
        }
        $storage = new ImageStorageService();
        try {
            $metadata = $storage->store($upload);
            $title = trim((string)$this->getRequest()->getData('title'));
            $entity = $this->fetchTable('ImagePrintJobs')->newEntity($metadata + [
                'title' => $title !== '' ? $title : pathinfo($metadata['original_name'], PATHINFO_FILENAME),
            ]);
            $entity->set('user_id', $this->currentUserId());
            if (!$this->fetchTable('ImagePrintJobs')->save($entity)) {
                $storage->delete($metadata['storage_name']);
                $this->Flash->error('入力内容を確認してください。');
            } else {
                $this->Flash->success('画像を保存しました。');
            }
        } catch (Throwable $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /** Return a private inline preview of an owned image. */
    public function preview(int $id): Response
    {
        $image = $this->ownedImage($id);
        $path = (new ImageStorageService())->path((string)$image->get('storage_name'));
        $body = is_file($path) ? file_get_contents($path) : false;
        if ($body === false) {
            throw new NotFoundException();
        }

        return $this->getResponse()
            ->withType((string)$image->get('mime_type'))
            ->withHeader('Cache-Control', 'private, no-store')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withStringBody($body);
    }

    /** Delete an owned image and its private file. */
    public function delete(int $id): Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $image = $this->ownedImage($id);
        (new ImageStorageService())->delete((string)$image->get('storage_name'));
        $this->fetchTable('ImagePrintJobs')->deleteOrFail($image);
        $this->Flash->success('画像を削除しました。');

        return $this->redirect(['action' => 'index']);
    }

    /** Send an owned image to an owned raster-capable printer. */
    public function printNow(int $id): Response
    {
        $this->getRequest()->allowMethod(['post']);
        $uid = $this->currentUserId();
        $image = $this->ownedImage($id);
        $printer = $this->fetchTable('Printers')->find()->where([
            'id' => (int)$this->getRequest()->getData('printer_id'),
            'user_id' => $uid,
            'raster_enabled' => true,
        ])->first();
        if (!$printer) {
            throw new NotFoundException();
        }
        $status = 'success';
        $message = '送信しました。';
        try {
            $path = (new ImageStorageService())->path((string)$image->get('storage_name'));
            (new ImagePrintService())->print($path, $printer->toArray());
            $this->Flash->success($message);
        } catch (Throwable $exception) {
            $status = 'failed';
            $message = mb_substr($exception->getMessage(), 0, 500);
            $this->Flash->error($message);
        }
        $log = $this->fetchTable('PrintLogs')->newEntity([
            'user_id' => $uid,
            'printer_id' => $printer->get('id'),
            'print_job_id' => null,
            'address_label_id' => null,
            'image_print_job_id' => $image->get('id'),
            'document_type' => 'image',
            'status' => $status,
            'message' => $message,
            'printed_at' => DateTime::now(),
        ]);
        $this->fetchTable('PrintLogs')->saveOrFail($log);

        return $this->redirect(['action' => 'index']);
    }

    /** Fetch an image only when it belongs to the logged-in user. */
    private function ownedImage(int $id): EntityInterface
    {
        $image = $this->fetchTable('ImagePrintJobs')->find()
            ->where(['id' => $id, 'user_id' => $this->currentUserId()])->first();
        if (!$image) {
            throw new NotFoundException();
        }

        return $image;
    }
}
