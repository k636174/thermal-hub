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
        'dpi' => true,
        'printable_width_dots' => true,
        'label_width_mm' => true,
        'label_length_mm' => true,
        'raster_enabled' => true,
    ];
}
