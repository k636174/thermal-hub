<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class PrintJobsTable extends Table
{
    /** Configure table metadata and associations. */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('print_jobs');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Users');
        $this->hasMany('PrintLogs');
    }

    /** Configure validation. */
    public function validationDefault(Validator $v): Validator
    {
        return $v->notEmptyString('title')
            ->maxLength('title', 200)
            ->notEmptyString('body')
            ->maxLength('body', 100000)
            ->notEmptyString('paper_guide')
            ->inList('paper_guide', ['none', 'narrow', 'm5']);
    }

    /** Configure application rules. */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules->add($rules->existsIn(['user_id'], 'Users'));
    }
}
