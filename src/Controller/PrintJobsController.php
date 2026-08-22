<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\EscPosPrinterService;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\DateTime;
use Throwable;

class PrintJobsController extends AppController
{
    public function index()
    {
        $uid = $this->currentUserId();
        $jobs = $this->fetchTable('PrintJobs')->find()->where(['user_id' => $uid])->all();
        $printers = $this->fetchTable('Printers')->find('list')->where(['user_id' => $uid])->toArray();
        $this->set(compact('jobs', 'printers'));
    }

    public function add()
    {
        $table = $this->fetchTable('PrintJobs');
        $printJob = $table->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $printJob = $table->patchEntity($printJob, $this->getRequest()->getData());
            $printJob->user_id = $this->currentUserId();
            if ($table->save($printJob)) {
                $this->Flash->success('印字データを保存しました。');

                return $this->redirect(['action' => 'index']);
            }
        } $this->set(compact('printJob'));
    }

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
        } $this->set(compact('printJob'));
    }

    public function delete(int $id)
    {
        $this->getRequest()->allowMethod(['post','delete']);
        $this->fetchTable('PrintJobs')->deleteOrFail($this->ownedJob($id));
        $this->Flash->success('印字データを削除しました。');

        return $this->redirect(['action' => 'index']);
    }

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
        $status = 'success';
        $message = '送信しました。';
        try {
            (new EscPosPrinterService())->print($printer->host, $printer->port, $job->body, $printer->encoding, $printer->timeout);
            $this->Flash->success($message);
        } catch (Throwable $e) {
            $status = 'failed';
            $message = mb_substr($e->getMessage(), 0, 500);
            $this->Flash->error($message);
        }
        $logs = $this->fetchTable('PrintLogs');
        $log = $logs->newEntity(['user_id' => $uid, 'printer_id' => $printer->id, 'print_job_id' => $job->id, 'status' => $status, 'message' => $message, 'printed_at' => DateTime::now()]);
        $logs->saveOrFail($log);

        return $this->redirect(['action' => 'index']);
    }

    private function ownedJob(int $id)
    {
        $job = $this->fetchTable('PrintJobs')->find()->where(['id' => $id, 'user_id' => $this->currentUserId()])->first();
        if (!$job) {
            throw new NotFoundException();
        }

        return $job;
    }
}
