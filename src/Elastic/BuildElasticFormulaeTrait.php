<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Elastic;

use Yiisoft\Db\Expression\Expression;

/**
 * Override of BuildFormulaeTrait for queries with elastic JSON virtual columns.
 *
 * Virtual columns are resolved to JSON_VALUE() expressions before calling the callback.
 * Regular columns are delegated to parent::buildFormulae().
 *
 * Requires BaseElasticQueryTrait on the same class (uses isVirtualColumn(), buildVirtualColumn()).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait BuildElasticFormulaeTrait
{
    public function buildFormulae(callable $callback, array $columns, array $params): Expression
    {
        $elasticParts = [];
        $regularColumns = [];

        foreach ($columns as $column) {
            if ($this->isVirtualColumn($column) === true) {
                $virtualColumnExpression = $this->buildVirtualColumn($column);
                $elasticParts[] = $callback((string) $virtualColumnExpression);
            } else {
                $regularColumns[] = $column;
            }
        }

        if (empty($regularColumns) === false) {
            $parentExpression = parent::buildFormulae($callback, $regularColumns, $params);
            $elasticParts[] = (string) $parentExpression;
        }

        return new Expression(implode(' + ', $elasticParts), $params);
    }
}
