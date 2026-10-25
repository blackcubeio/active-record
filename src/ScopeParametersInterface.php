<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord;

/**
 * Typed parameter bag for scope invocation.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
interface ScopeParametersInterface
{
    /**
     * Retrieve a parameter value by name.
     *
     * @return mixed null if the parameter was not provided
     */
    public function value(string $propertyName): mixed;
}
