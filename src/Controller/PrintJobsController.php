<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\EscPosPrinterService;
use App\Service\PrintRangeGuideService;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Throwable;

class PrintJobsController extends AppController
{
    /** @return void */
    public function index()
    {
        $uid = $this->currentUserId();
        $jobs = $this->fetchTable('PrintJobs')->find()->where(['user_id' => $uid])->all();
        $printers = $this->fetchTable('Printers')->find('list')->where(['user_id' => $uid])->toArray();
        $printGuides = [];
        $guideService = new PrintRangeGuideService();
        foreach ($jobs as $job) {
            $printGuides[(int)$job->get('id')] = $guideService->analyze((string)$job->get('body'));
        }
        $paperGuideLimits = [
            'narrow' => PrintRangeGuideService::NARROW_LINE_LIMIT,
            'm5' => PrintRangeGuideService::M5_LINE_LIMIT,
        ];
        $this->set(compact('jobs', 'printers', 'printGuides', 'paperGuideLimits'));
    }

    /** @return \Cake\Http\Response|null */
    public function add()
    {
        $table = $this->fetchTable('PrintJobs');
        $printJob = $table->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $printJob = $table->patchEntity($printJob, $this->getRequest()->getData());
            $printJob->set('user_id', $this->currentUserId());
            if ($table->save($printJob)) {
                $this->Flash->success('印字データを保存しました。');

                return $this->redirect(['action' => 'index']);
            }
        }
        $this->setFormGuideData($printJob);

        return null;
    }

    /** @return \Cake\Http\Response|null */
    public function edit(int $id)
    {
        $table = $this->fetchTable('PrintJobs');
        $printJob = $this->ownedJob($id);
        if ($this->getRequest()->is(['patch','post','put'])) {
            $printJob = $table->patchEntity($printJob, $this->getRequest()->getData());
            if ($table->save($printJob)) {
                $this->Flash->success('印字データを更新しました。');

                return $this->redirect(['action' => 'index']);
            }
        }
        $this->setFormGuideData($printJob);

        return null;
    }

    /** @return \Cake\Http\Response|null */
    public function delete(int $id)
    {
        $this->getRequest()->allowMethod(['post','delete']);
        $this->fetchTable('PrintJobs')->deleteOrFail($this->ownedJob($id));
        $this->Flash->success('印字データを削除しました。');

        return $this->redirect(['action' => 'index']);
    }

    /** Preview unsaved print data without persisting it. */
    public function preview(): Response
    {
        $this->getRequest()->allowMethod(['post', 'put', 'patch']);
        $table = $this->fetchTable('PrintJobs');
        $printJob = $table->patchEntity($table->newEmptyEntity(), $this->getRequest()->getData());
        if ($printJob->hasErrors()) {
            throw new BadRequestException('入力内容を確認してください。');
        }

        $this->set(compact('printJob'));

        return $this->render('preview');
    }

    /** @return \Cake\Http\Response|null */
    public function printNow(int $id)
    {
        $this->getRequest()->allowMethod(['post']);
        $uid = $this->currentUserId();
        $job = $this->ownedJob($id);
        $printerId = (int)$this->getRequest()->getData('printer_id');
        $printer = $this->fetchTable('Printers')->find()->where(['id' => $printerId, 'user_id' => $uid])->first();
        if (!$printer) {
            throw new NotFoundException();
        }
        $job->set('last_printer_id', $printer->get('id'));
        $paperGuide = $this->normalizedPaperGuide($this->getRequest()->getData('paper_guide'));
        $job->set('paper_guide', $paperGuide);
        $job->set('last_paper_guide', $paperGuide);
        $this->fetchTable('PrintJobs')->saveOrFail($job);
        $status = 'success';
        $message = '送信しました。';
        try {
            (new EscPosPrinterService())->print(
                (string)$printer->get('host'),
                (int)$printer->get('port'),
                (string)$job->get('body'),
                (string)$printer->get('encoding'),
                (int)$printer->get('timeout'),
            );
            $this->Flash->success($message);
        } catch (Throwable $e) {
            $status = 'failed';
            $message = mb_substr($e->getMessage(), 0, 500);
            $this->Flash->error($message);
        }
        $logs = $this->fetchTable('PrintLogs');
        $log = $logs->newEntity([
            'user_id' => $uid,
            'printer_id' => $printer->get('id'),
            'print_job_id' => $job->get('id'),
            'address_label_id' => null,
            'document_type' => 'text',
            'status' => $status,
            'message' => $message,
            'printed_at' => DateTime::now(),
        ]);
        $logs->saveOrFail($log);

        return $this->redirect(['action' => 'index']);
    }

    /** @return \Cake\Datasource\EntityInterface */
    private function ownedJob(int $id)
    {
        $job = $this->fetchTable('PrintJobs')->find()
            ->where(['id' => $id, 'user_id' => $this->currentUserId()])->first();
        if (!$job) {
            throw new NotFoundException();
        }

        return $job;
    }

    /** Supply shared paper-guide data to the add/edit form. */
    private function setFormGuideData(object $printJob): void
    {
        $paperGuideLimits = [
            'narrow' => PrintRangeGuideService::NARROW_LINE_LIMIT,
            'm5' => PrintRangeGuideService::M5_LINE_LIMIT,
        ];
        $this->set(compact('printJob', 'paperGuideLimits'));
    }

    /** Return a supported guide value even for a manipulated request. */
    private function normalizedPaperGuide(mixed $value): string
    {
        return in_array($value, ['narrow', 'm5'], true) ? $value : 'none';
    }
}
