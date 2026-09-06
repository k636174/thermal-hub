<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class WeeklyScheduleEntry extends Entity
{
    protected array $_accessible = [
        'schedule_date' => true,
        'note' => true,
        'is_inverted' => true,
    ];
}
