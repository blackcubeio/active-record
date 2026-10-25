<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord;

/**
 * Immutable parameter bag backed by a simple array.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ScopeParameters implements ScopeParametersInterface
{
    public function __construct(private readonly array $parameters = [])
    {
    }

    public function value(string $propertyName): mixed
    {
        return $this->parameters[$propertyName] ?? null;
    }
}
