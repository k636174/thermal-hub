<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use DateTimeImmutable;

class WeeklyScheduleEntriesTable extends Table
{
    /** Configure table metadata and associations. */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('weekly_schedule_entries');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Users');
    }

    /** Configure validation. */
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->date('schedule_date')->notEmptyDate('schedule_date')
            ->allowEmptyString('note')->maxLength('note', 120)
            ->boolean('is_inverted');
    }

    /** Configure ownership and uniqueness rules. */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules
            ->add($rules->existsIn(['user_id'], 'Users'))
            ->add($rules->isUnique(['user_id', 'schedule_date']));
    }

    /**
     * Persist all seven days atomically for one owner.
     *
     * @param array<int, string> $notes
     * @param array<int, int> $invertedDays
     */
    public function saveWeek(int $userId, DateTimeImmutable $weekStart, array $notes, array $invertedDays): void
    {
        $inverted = array_fill_keys(array_map('intval', $invertedDays), true);
        $this->getConnection()->transactional(function () use ($userId, $weekStart, $notes, $inverted): void {
            for ($index = 0; $index < 7; $index++) {
                $date = $weekStart->modify('+' . $index . ' days')->format('Y-m-d');
                $entry = $this->find()
                    ->where(['user_id' => $userId, 'schedule_date' => $date])
                    ->first() ?? $this->newEmptyEntity();
                $entry = $this->patchEntity($entry, [
                    'schedule_date' => $date,
                    'note' => $notes[$index] ?? '',
                    'is_inverted' => isset($inverted[$index]),
                ]);
                $entry->set('user_id', $userId);
                $this->saveOrFail($entry);
            }
        });
    }
}
