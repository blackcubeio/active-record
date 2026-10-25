<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord;

use Blackcube\ActiveRecord\Hazeltree\HazeltreeInterface;
use Blackcube\ActiveRecord\Hazeltree\HazeltreeTrait;
use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * Convenience base class for ActiveRecord models with tree structure.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
abstract class AbstractHazeltreeActiveRecord extends ActiveRecord implements HazeltreeInterface
{
    use HazeltreeTrait;
}
