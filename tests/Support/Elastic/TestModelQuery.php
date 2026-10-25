<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Elastic;

use Blackcube\ActiveRecord\BuildFormulaeTrait;
use Blackcube\ActiveRecord\Elastic\BaseElasticQueryTrait;
use Blackcube\ActiveRecord\Elastic\BuildElasticFormulaeTrait;
use Blackcube\ActiveRecord\Elastic\ElasticQueryInterface;
use Blackcube\ActiveRecord\FormulaeExpressionInterface;
use Blackcube\ActiveRecord\QualifyColumnTrait;
use Yiisoft\ActiveRecord\ActiveQuery;

/**
 * Base query with column qualification and formulae support.
 * ElasticQueryTrait is added in the child class to avoid trait collision on where/andWhere/orWhere.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class BaseTestModelQuery extends ActiveQuery
{
    use QualifyColumnTrait;
    use BuildFormulaeTrait;
}

/**
 * Query class for elastic test models.
 * Inherits qualification from BaseTestModelQuery, adds elastic virtual column support.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class TestModelQuery extends BaseTestModelQuery implements ElasticQueryInterface, FormulaeExpressionInterface
{
    use BaseElasticQueryTrait;
    use BuildElasticFormulaeTrait;

    public static function create(string $modelClass): static
    {
        return new static($modelClass);
    }
}
