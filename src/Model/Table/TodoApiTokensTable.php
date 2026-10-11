<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class TodoApiTokensTable extends Table
{
    /** Configure table metadata and associations. */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('todo_api_tokens');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Users');
    }

    /** Validate stored token metadata. */
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->regex('token_hash', '/^[a-f0-9]{64}$/')->notEmptyString('token_hash')
            ->scalar('token_hint')->maxLength('token_hint', 12)->notEmptyString('token_hint');
    }

    /** Enforce token ownership and uniqueness. */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules
            ->add($rules->existsIn(['user_id'], 'Users'))
            ->add($rules->isUnique(['user_id']))
            ->add($rules->isUnique(['token_hash']));
    }
}
