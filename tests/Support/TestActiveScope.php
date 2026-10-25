<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support;

use Blackcube\ActiveRecord\ScopeInterface;
use Blackcube\ActiveRecord\ScopableQueryInterface;
use Blackcube\ActiveRecord\ScopeParametersInterface;

/**
 * Test scope: filter by active flag.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class TestActiveScope implements ScopeInterface
{
    public static function name(): string
    {
        return 'active';
    }

    public function process(ScopableQueryInterface $query, string $modelClass, ?ScopeParametersInterface $parameters = null): void
    {
        if (property_exists($modelClass, 'active') === false) {
            return;
        }

        $query->andWhere(['active' => $parameters?->value('active') ?? true]);
    }
}
