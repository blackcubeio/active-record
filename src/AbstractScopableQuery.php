<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord;

use Yiisoft\ActiveRecord\ActiveQuery;

/**
 * Base query with scope support, column qualification, and formulae.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
abstract class AbstractScopableQuery extends ActiveQuery implements ScopableQueryInterface, FormulaeExpressionInterface
{
    use ScopableTrait;
    use QualifyColumnTrait;
    use BuildFormulaeTrait;
    use BatchedEachQueryTrait;
}
