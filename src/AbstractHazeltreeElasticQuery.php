<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord;

use Blackcube\ActiveRecord\Elastic\BaseElasticQueryTrait;
use Blackcube\ActiveRecord\Elastic\BuildElasticFormulaeTrait;
use Blackcube\ActiveRecord\Elastic\ElasticQueryInterface;
use Blackcube\ActiveRecord\Hazeltree\BaseHazeltreeQueryTrait;
use Blackcube\ActiveRecord\Hazeltree\HazeltreeQueryInterface;

/**
 * Base query with scope support + elastic JSON virtual columns + tree navigation.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
abstract class AbstractHazeltreeElasticQuery extends AbstractScopableQuery implements ElasticQueryInterface, HazeltreeQueryInterface
{
    use BaseElasticQueryTrait;
    use BaseHazeltreeQueryTrait;
    use BuildElasticFormulaeTrait;
}
