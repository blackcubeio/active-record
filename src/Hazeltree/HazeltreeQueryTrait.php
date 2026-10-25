<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Hazeltree;

/**
 * Composite query trait for tree navigation.
 *
 * Simply re-exports BaseHazeltreeQueryTrait. No collision to resolve for queries.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait HazeltreeQueryTrait
{
    use BaseHazeltreeQueryTrait;
}
