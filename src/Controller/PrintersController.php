<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\NotFoundException;

class PrintersController extends AppController
{
    /** @return void */
    public function index()
    {
        $printers = $this->fetchTable('Printers')->find()->where(['user_id' => $this->currentUserId()])->all();
        $this->set(compact('printers'));
    }

    /** @return \Cake\Http\Response|null */
    public function add()
    {
        $table = $this->fetchTable('Printers');
        $printer = $table->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $printer = $table->patchEntity($printer, $this->getRequest()->getData());
            $printer->set('user_id', $this->currentUserId());
            if ($table->save($printer)) {
                $this->Flash->success('プリンターを登録しました。');

                return $this->redirect(['action' => 'index']);
            }
        }
        $this->set(compact('printer'));

        return null;
    }

    /** @return \Cake\Http\Response|null */
    public function edit(int $id)
    {
        $table = $this->fetchTable('Printers');
        $printer = $table->find()->where(['id' => $id, 'user_id' => $this->currentUserId()])->first();
        if (!$printer) {
            throw new NotFoundException();
        }
        if ($this->getRequest()->is(['patch','post','put'])) {
            $printer = $table->patchEntity($printer, $this->getRequest()->getData());
            if ($table->save($printer)) {
                $this->Flash->success('プリンターを更新しました。');

                return $this->redirect(['action' => 'index']);
            }
        }
        $this->set(compact('printer'));

        return null;
    }

    /** @return \Cake\Http\Response|null */
    public function delete(int $id)
    {
        $this->getRequest()->allowMethod(['post','delete']);
        $table = $this->fetchTable('Printers');
        $printer = $table->find()->where(['id' => $id, 'user_id' => $this->currentUserId()])->first();
        if (!$printer) {
            throw new NotFoundException();
        } $table->deleteOrFail($printer);
        $this->Flash->success('プリンターを削除しました。');

        return $this->redirect(['action' => 'index']);
    }
}
