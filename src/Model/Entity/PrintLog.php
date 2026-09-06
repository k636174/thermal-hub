<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class PrintLog extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'printer_id' => true,
        'print_job_id' => true,
        'address_label_id' => true,
        'image_print_job_id' => true,
        'document_type' => true,
        'status' => true,
        'message' => true,
        'printed_at' => true,
    ];
}
