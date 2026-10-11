<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class TodoApiToken extends Entity
{
    protected array $_accessible = [
        'token_hash' => true,
        'token_hint' => true,
    ];
}
