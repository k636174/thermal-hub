<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Service\ImageStorageService;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class ImagePrintJobsTable extends Table
{
    /** Configure image print job persistence. */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('image_print_jobs');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Users');
        $this->belongsTo('LastPrinters', [
            'className' => 'Printers',
            'foreignKey' => 'last_printer_id',
        ]);
        $this->hasMany('PrintLogs');
    }

    /** Validate stored image metadata. */
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->notEmptyString('title')->maxLength('title', 200)
            ->notEmptyString('storage_name')->maxLength('storage_name', 80)
            ->notEmptyString('original_name')->maxLength('original_name', 255)
            ->inList('mime_type', ['image/jpeg', 'image/png', 'image/webp'])
            ->integer('file_size')->range('file_size', [1, ImageStorageService::MAX_FILE_SIZE]);
    }

    /** Require an existing owner and unique generated filename. */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules
            ->add($rules->existsIn(['user_id'], 'Users'))
            ->add($rules->isUnique(['storage_name']));
    }
}
