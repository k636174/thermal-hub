<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Todo extends Entity
{
    protected array $_accessible = [
        'title' => true,
        'description' => true,
        'status' => true,
        'priority' => true,
        'due_date' => true,
        'is_completed' => true,
    ];
}
