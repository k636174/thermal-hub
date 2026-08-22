<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Printer extends Entity
{
    protected array $_accessible = [
        'name' => true,
        'host' => true,
        'port' => true,
        'encoding' => true,
        'timeout' => true,
    ];
}
