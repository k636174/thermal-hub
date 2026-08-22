<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class PrintersTable extends Table
{
    /** Configure table metadata and associations. */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('printers');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Users');
        $this->hasMany('PrintLogs');
    }

    /** Configure validation. */
    public function validationDefault(Validator $v): Validator
    {
        return $v->notEmptyString('name')->maxLength('name', 100)->notEmptyString('host')->maxLength('host', 255)
            ->integer('port')->range('port', [1, 65535])->integer('timeout')->range('timeout', [1, 30])
            ->inList('encoding', ['CP932', 'SHIFT_JIS', 'UTF-8']);
    }

    /** Configure application rules. */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules->add($rules->existsIn(['user_id'], 'Users'));
    }
}
