<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord;

use Yiisoft\Db\Expression\Expression;

/**
 * Base implementation of FormulaeExpressionInterface for regular (non-virtual) columns.
 *
 * Requires QualifyColumnTrait on the same class (uses ensureTableName() and getQualifier()).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait BuildFormulaeTrait
{
    public function buildFormulae(callable $callback, array $columns, array $params): Expression
    {
        $this->ensureTableName();
        $qualifier = $this->getQualifier();

        $parts = [];
        foreach ($columns as $col) {
            $parts[] = $callback($qualifier . '.[[' . $col . ']]');
        }

        return new Expression(implode(' + ', $parts), $params);
    }
}
