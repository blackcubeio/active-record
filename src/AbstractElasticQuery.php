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

/**
 * Base query with scope support + elastic JSON virtual columns.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
abstract class AbstractElasticQuery extends AbstractScopableQuery implements ElasticQueryInterface
{
    use BaseElasticQueryTrait;
    use BuildElasticFormulaeTrait;
}
