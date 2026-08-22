<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AddressLabel extends Entity
{
    protected array $_accessible = [
        'title' => true,
        'postal_code' => true,
        'address_line1' => true,
        'address_line2' => true,
        'recipient_name' => true,
        'honorific' => true,
    ];
}
