<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support;

use Blackcube\ActiveRecord\BuildFormulaeTrait;
use Blackcube\ActiveRecord\FormulaeExpressionInterface;
use Blackcube\ActiveRecord\QualifyColumnTrait;
use Blackcube\ActiveRecord\ScopableTrait;
use Blackcube\ActiveRecord\ScopableQueryInterface;
use Yiisoft\ActiveRecord\ActiveQuery;

/**
 * Test query using ScopableTrait, QualifyColumnTrait and BuildFormulaeTrait.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class TestScopableQuery extends ActiveQuery implements ScopableQueryInterface, FormulaeExpressionInterface
{
    use ScopableTrait;
    use QualifyColumnTrait;
    use BuildFormulaeTrait;

    public static function create(string $modelClass): static
    {
        return new static($modelClass);
    }
}
