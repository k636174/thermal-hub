<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class UsersTable extends Table
{
    /** Configure table metadata and associations. */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('users');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->hasMany('Printers');
        $this->hasMany('PrintJobs');
        $this->hasMany('PrintLogs');
    }

    /** Configure validation. */
    public function validationDefault(Validator $validator): Validator
    {
        return $validator->email('email')->requirePresence('email', 'create')->notEmptyString('email')
            ->scalar('name')->maxLength('name', 100)->notEmptyString('name')
            ->scalar('password')->minLength('password', 8)
            ->requirePresence('password', 'create')->notEmptyString('password');
    }

    /** Configure application rules. */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules->add($rules->isUnique(['email']), ['errorField' => 'email']);
    }
}
