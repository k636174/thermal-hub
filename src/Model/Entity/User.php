<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class User extends Entity
{
    protected array $_accessible = ['email' => true, 'name' => true, 'password' => true];
    protected array $_hidden = ['password'];

    /** パスワードを保存前にハッシュ化する。 */
    protected function _setPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
