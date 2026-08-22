<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class PrintJobsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('print_jobs');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Users');
        $this->hasMany('PrintLogs');
    }

    public function validationDefault(Validator $v): Validator
    {
        return $v->notEmptyString('title')->maxLength('title', 200)->notEmptyString('body')->maxLength('body', 100000);
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules->add($rules->existsIn(['user_id'], 'Users'));
    }
}
