<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Elastic;

/**
 * Simple class (not ActiveRecord) for testing elastic detection.
 * Originally used #[Elastic] attribute which no longer exists.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class SimpleClassWithElastic
{
    public ?string $name = null;
    public ?string $email = null;
}
