<?php
declare(strict_types=1);

namespace App\Controller;

class UsersController extends AppController
{
    public function login()
    {
        if ($this->getRequest()->getSession()->read('Auth.User.id')) {
            return $this->redirect(['controller' => 'PrintJobs', 'action' => 'index']);
        }
        if ($this->getRequest()->is('post')) {
            $email = mb_strtolower(trim((string)$this->getRequest()->getData('email')));
            $user = $this->fetchTable('Users')->find()->where(['email' => $email])->first();
            if ($user && password_verify((string)$this->getRequest()->getData('password'), $user->password)) {
                $this->getRequest()->getSession()->renew();
                $this->getRequest()->getSession()->write('Auth.User', ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]);

                return $this->redirect(['controller' => 'PrintJobs', 'action' => 'index']);
            }
            $this->Flash->error('メールアドレスまたはパスワードが正しくありません。');
        }
    }

    public function logout()
    {
        $this->getRequest()->allowMethod(['post']);
        $this->getRequest()->getSession()->destroy();

        return $this->redirect(['action' => 'login']);
    }
}
