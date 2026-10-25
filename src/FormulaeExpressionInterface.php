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
 * Interface for queries that can build computed expressions from column lists.
 *
 * The callback receives a fully qualified column reference (e.g. `{{%table}}.[[col]]`
 * or a JSON_VALUE expression) and returns a SQL fragment. The implementation
 * assembles all fragments and returns a single Expression with bound parameters.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
interface FormulaeExpressionInterface
{
    /**
     * @param callable(string): string $callback Receives a qualified column, returns a SQL fragment
     * @param list<string> $columns Column names (real or virtual)
     * @param array $params Parameters to bind in the returned Expression
     */
    public function buildFormulae(callable $callback, array $columns, array $params): Expression;
}
