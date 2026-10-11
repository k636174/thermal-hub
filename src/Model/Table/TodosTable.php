<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class TodosTable extends Table
{
    /** Configure table metadata and associations. */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('todos');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Users');
    }

    /** Configure Todo input validation. */
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->scalar('title')->maxLength('title', 500)->notEmptyString('title')
            ->scalar('description')->allowEmptyString('description')
            ->inList('status', ['pending', 'in_progress', 'completed'])->notEmptyString('status')
            ->inList('priority', ['low', 'medium', 'high'])->notEmptyString('priority')
            ->dateTime('due_date')->allowEmptyDateTime('due_date')
            ->boolean('is_completed');
    }

    /** Ensure each Todo belongs to an existing user. */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules->add($rules->existsIn(['user_id'], 'Users'));
    }
}
