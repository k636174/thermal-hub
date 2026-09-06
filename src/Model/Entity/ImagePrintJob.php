<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class ImagePrintJob extends Entity
{
    protected array $_accessible = [
        'title' => true,
        'storage_name' => true,
        'original_name' => true,
        'mime_type' => true,
        'file_size' => true,
        'last_printer_id' => true,
    ];
}
