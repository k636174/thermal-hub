<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class TodoPrintPreferencesTable extends Table
{
    /** Configure table metadata and associations. */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('todo_print_preferences');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Users');
        $this->belongsTo('Printers');
    }

    /** Validate the supported planner sizes. */
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->integer('printer_id')->allowEmptyString('printer_id')
            ->inList('paper_guide', ['narrow', 'm5'])->notEmptyString('paper_guide');
    }

    /** Ensure preference owners and printers exist. */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules
            ->add($rules->existsIn(['user_id'], 'Users'))
            ->add($rules->existsIn(['printer_id'], 'Printers'), ['errorField' => 'printer_id'])
            ->add($rules->isUnique(['user_id']));
    }
}
