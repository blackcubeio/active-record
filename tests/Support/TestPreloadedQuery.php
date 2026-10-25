<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support;

use Blackcube\ActiveRecord\QualifyColumnTrait;
use Blackcube\ActiveRecord\ScopableTrait;
use Blackcube\ActiveRecord\ScopableQueryInterface;
use Yiisoft\ActiveRecord\ActiveQuery;

/**
 * Test query with scopes pre-registered in create() (doc example: ProductQuery).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class TestPreloadedQuery extends ActiveQuery implements ScopableQueryInterface
{
    use ScopableTrait;
    use QualifyColumnTrait;

    public static function create(string $modelClass): static
    {
        $query = new static($modelClass);
        $query->addScope(TestActiveScope::class)
              ->addScope(TestLanguageScope::class);
        return $query;
    }
}
