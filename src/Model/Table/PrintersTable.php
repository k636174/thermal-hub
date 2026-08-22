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
            ->inList('encoding', ['CP932', 'SHIFT_JIS', 'UTF-8'])
            ->integer('dpi')->inList('dpi', [203])
            ->integer('printable_width_dots')->range('printable_width_dots', [128, 832])
            ->decimal('label_width_mm')->range('label_width_mm', [20, 300])
            ->decimal('label_length_mm')->range('label_length_mm', [20, 300])
            ->boolean('raster_enabled')
            ->add('label_width_mm', 'fitsPrintableWidth', [
                'rule' => static function (mixed $value, array $context): bool {
                    $data = $context['data'];
                    $dpi = (int)($data['dpi'] ?? 203);
                    $available = (int)($data['printable_width_dots'] ?? 576);

                    return (int)round((float)$value * $dpi / 25.4) <= $available;
                },
                'message' => '用紙幅がプリンターの印字可能幅を超えています。',
            ]);
    }

    /** Configure application rules. */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules->add($rules->existsIn(['user_id'], 'Users'));
    }
}
