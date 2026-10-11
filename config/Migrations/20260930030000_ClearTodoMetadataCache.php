<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Migrations\BaseMigration;

class ClearTodoMetadataCache extends BaseMigration
{
    /** Invalidate schema metadata cached before the Todo API fields existed. */
    public function up(): void
    {
        Cache::clear('_cake_model_');
    }

    /** Cache invalidation does not need to be rolled back. */
    public function down(): void
    {
    }
}
