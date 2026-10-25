<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Hazeltree;

use Blackcube\ActiveRecord\Hazeltree\BaseHazeltreeQueryTrait;
use Yiisoft\ActiveRecord\ActiveQuery;

/**
 * ActiveQuery for TreeNode with tree navigation capabilities.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class TreeNodeQuery extends ActiveQuery
{
    use BaseHazeltreeQueryTrait;
}
