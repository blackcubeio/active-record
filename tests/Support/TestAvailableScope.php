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
 * Test scope: reads referenceDate from state and filters (doc example).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class TestAvailableScope implements ScopeInterface
{
    public static function name(): string
    {
        return 'available';
    }

    public function process(ScopableQueryInterface $query, string $modelClass, ?ScopeParametersInterface $parameters = null): void
    {
        $referenceDate = $query->getState('referenceDate');
        if ($referenceDate !== null) {
            $query->setState('availableUsedDate', $referenceDate);
        }
    }
}
