<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class TodoPrintPreference extends Entity
{
    protected array $_accessible = [
        'printer_id' => true,
        'paper_guide' => true,
    ];
}
