<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\BatchedEager;

use Blackcube\ActiveRecord\BatchedEachQueryTrait;
use Yiisoft\ActiveRecord\ActiveQuery;

/**
 * Test query using BatchedEachQueryTrait, exactly like AbstractScopableQuery in prod,
 * so each() goes through the batched eager-loading path.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class EachTestQuery extends ActiveQuery
{
    use BatchedEachQueryTrait;

    public static function create(string $modelClass): static
    {
        return new static($modelClass);
    }
}
