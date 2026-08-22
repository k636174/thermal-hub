<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AddressLabelLayoutService;
use App\Service\AddressLabelPrintService;
use App\Service\RasterImageService;
use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Throwable;

class AddressLabelsController extends AppController
{
    /** List owned labels. */
    public function index(): void
    {
        $uid = $this->currentUserId();
        $labels = $this->fetchTable('AddressLabels')->find()->where(['user_id' => $uid])->all();
        $printers = $this->fetchTable('Printers')->find('list')
            ->where(['user_id' => $uid, 'raster_enabled' => true])->toArray();
        $this->set(compact('labels', 'printers'));
    }

    /** Add a label. */
    public function add(): ?Response
    {
        $table = $this->fetchTable('AddressLabels');
        $addressLabel = $table->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $addressLabel = $table->patchEntity($addressLabel, $this->getRequest()->getData());
            $addressLabel->set('user_id', $this->currentUserId());
            if ($table->save($addressLabel)) {
                $this->Flash->success('宛先ラベルを保存しました。');

                return $this->redirect(['action' => 'index']);
            }
        }
        $this->set(compact('addressLabel'));

        return null;
    }

    /** Edit a label. */
    public function edit(int $id): ?Response
    {
        $table = $this->fetchTable('AddressLabels');
        $addressLabel = $this->ownedLabel($id);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $addressLabel = $table->patchEntity($addressLabel, $this->getRequest()->getData());
            if ($table->save($addressLabel)) {
                $this->Flash->success('宛先ラベルを更新しました。');

                return $this->redirect(['action' => 'index']);
            }
        }
        $this->set(compact('addressLabel'));

        return null;
    }

    /** Delete a label. */
    public function delete(int $id): Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $this->fetchTable('AddressLabels')->deleteOrFail($this->ownedLabel($id));
        $this->Flash->success('宛先ラベルを削除しました。');

        return $this->redirect(['action' => 'index']);
    }

    /** Render an unsaved label preview. */
    public function preview(): Response
    {
        $this->getRequest()->allowMethod(['post', 'put', 'patch']);
        $table = $this->fetchTable('AddressLabels');
        $label = $table->patchEntity($table->newEmptyEntity(), $this->getRequest()->getData());
        if ($label->hasErrors()) {
            throw new BadRequestException('入力内容を確認してください。');
        }
        $layout = new AddressLabelLayoutService();
        $svg = $layout->renderSvg($label->toArray(), 203, 72.0, 100.0);
        $raster = new RasterImageService();
        $image = $raster->render($svg, 576);
        $preview = $raster->orientForPreview($image['png'], $layout->physicalPaperWidthDots(203));

        return $this->getResponse()
            ->withType('png')
            ->withHeader('Cache-Control', 'no-store')
            ->withStringBody($preview);
    }

    /** Print a saved label immediately. */
    public function printNow(int $id): Response
    {
        $this->getRequest()->allowMethod(['post']);
        $uid = $this->currentUserId();
        $label = $this->ownedLabel($id);
        $printerId = (int)$this->getRequest()->getData('printer_id');
        $printer = $this->fetchTable('Printers')->find()
            ->where(['id' => $printerId, 'user_id' => $uid])->first();
        if (!$printer) {
            throw new NotFoundException();
        }

        $status = 'success';
        $message = '送信しました。';
        try {
            (new AddressLabelPrintService())->print($label->toArray(), $printer->toArray());
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
            'address_label_id' => $label->get('id'),
            'document_type' => 'address_label',
            'status' => $status,
            'message' => $message,
            'printed_at' => DateTime::now(),
        ]);
        $this->fetchTable('PrintLogs')->saveOrFail($log);

        return $this->redirect(['action' => 'index']);
    }

    /** Fetch one label owned by the logged-in user. */
    private function ownedLabel(int $id): EntityInterface
    {
        $label = $this->fetchTable('AddressLabels')->find()
            ->where(['id' => $id, 'user_id' => $this->currentUserId()])->first();
        if (!$label) {
            throw new NotFoundException();
        }

        return $label;
    }
}
