<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord;

use Yiisoft\Db\Query\DataReaderInterface;

/**
 * Overrides each() so eager-loaded relations (with()) are batched.
 *
 * Yii3's ActiveQuery::each() populates row by row (populateOne → findWith on a
 * single model) → with() runs one query per row (N+1). batch() already populates
 * per batch (findWith on the whole batch = one query per relation), so here each()
 * reads through batch() and flattens it back to one model at a time.
 *
 * Use it explicitly on any ActiveQuery class that should batch its eager loading.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait BatchedEachQueryTrait
{
    public function each(int $batchSize = 100): DataReaderInterface
    {
        return new BatchedDataReader($this->batch($batchSize));
    }
}
