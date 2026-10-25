<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord;

use Blackcube\ActiveRecord\Hazeltree\BaseHazeltreeQueryTrait;
use Blackcube\ActiveRecord\Hazeltree\HazeltreeQueryInterface;

/**
 * Base query with scope support + tree navigation.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
abstract class AbstractHazeltreeQuery extends AbstractScopableQuery implements HazeltreeQueryInterface
{
    use BaseHazeltreeQueryTrait;
}
