<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord;

use Blackcube\ActiveRecord\Elastic\ElasticInterface;
use Blackcube\ActiveRecord\Hazeltree\HazeltreeInterface;
use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * Convenience base class for ActiveRecord models with both elastic and tree.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
abstract class AbstractHazeltreeElasticActiveRecord extends ActiveRecord implements ElasticInterface, HazeltreeInterface
{
    use HazeltreeElasticTrait;
}
