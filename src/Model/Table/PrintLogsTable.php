<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class PrintLogsTable extends Table
{
    /** Configure table metadata and associations. */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('print_logs');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Users');
        $this->belongsTo('Printers');
        $this->belongsTo('PrintJobs');
        $this->belongsTo('AddressLabels');
    }
}
