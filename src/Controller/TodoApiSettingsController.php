<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\TodoApiTokenService;
use Cake\Http\Response;

/** Browser management for the current user's Todo API token. */
class TodoApiSettingsController extends AppController
{
    /** Display token status and a newly issued token once. */
    public function index(): void
    {
        $this->setResponse($this->getResponse()->withHeader('Cache-Control', 'no-store'));
        $token = $this->fetchTable('TodoApiTokens')->find()
            ->where(['user_id' => $this->currentUserId()])->first();
        $newToken = $this->getRequest()->getSession()->consume('TodoApi.generatedToken');
        $this->set(compact('token', 'newToken'));
    }

    /** Register a supplied token or generate a new random token. */
    public function save(): Response
    {
        $this->getRequest()->allowMethod(['post']);
        $service = new TodoApiTokenService();
        $tokenValue = trim((string)$this->getRequest()->getData('token'));
        if ($tokenValue === '') {
            $tokenValue = $service->generate();
        }
        if (strlen($tokenValue) < 32 || strlen($tokenValue) > 255) {
            $this->Flash->error('Bearerトークンは32〜255文字で設定してください。');

            return $this->redirect(['action' => 'index']);
        }

        $table = $this->fetchTable('TodoApiTokens');
        $token = $table->find()->where(['user_id' => $this->currentUserId()])->first()
            ?? $table->newEmptyEntity();
        $token->set('user_id', $this->currentUserId());
        $token->set('token_hash', $service->hash($tokenValue));
        $token->set('token_hint', $service->hint($tokenValue));
        $table->saveOrFail($token);
        $this->getRequest()->getSession()->write('TodoApi.generatedToken', $tokenValue);
        $this->Flash->success('Todo APIのBearerトークンを更新しました。');

        return $this->redirect(['action' => 'index']);
    }

    /** Revoke the current user's token. */
    public function delete(): Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $table = $this->fetchTable('TodoApiTokens');
        $token = $table->find()->where(['user_id' => $this->currentUserId()])->first();
        if ($token) {
            $table->deleteOrFail($token);
        }
        $this->getRequest()->getSession()->delete('TodoApi.generatedToken');
        $this->Flash->success('Todo APIのBearerトークンを無効化しました。');

        return $this->redirect(['action' => 'index']);
    }
}
