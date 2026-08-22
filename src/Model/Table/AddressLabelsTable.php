<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class AddressLabelsTable extends Table
{
    /** Configure table metadata and associations. */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('address_labels');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Users');
        $this->hasMany('PrintLogs');
    }

    /** Configure validation. */
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->notEmptyString('title')->maxLength('title', 200)
            ->allowEmptyString('postal_code')
            ->add('postal_code', 'format', [
                'rule' => static fn(mixed $value): bool => preg_match(
                    '/\A(?:\d{7}|\d{3}-\d{4})\z/',
                    (string)$value,
                ) === 1,
                'message' => '郵便番号は7桁の数字で入力してください。',
            ])
            ->notEmptyString('address_line1')->maxLength('address_line1', 255)
            ->allowEmptyString('address_line2')->maxLength('address_line2', 255)
            ->notEmptyString('recipient_name')->maxLength('recipient_name', 200)
            ->notEmptyString('honorific')->inList('honorific', ['様', '御中', '行', 'なし']);
    }

    /** Configure application rules. */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules->add($rules->existsIn(['user_id'], 'Users'));
    }

    /** Normalize values before validation. */
    public function beforeMarshal(EventInterface $event, ArrayObject $data): void
    {
        if (isset($data['postal_code'])) {
            $data['postal_code'] = str_replace('-', '', mb_convert_kana((string)$data['postal_code'], 'n'));
        }
    }
}
