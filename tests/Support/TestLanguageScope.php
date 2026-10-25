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
 * Test scope: filter by languageId.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class TestLanguageScope implements ScopeInterface
{
    public static function name(): string
    {
        return 'language';
    }

    public function process(ScopableQueryInterface $query, string $modelClass, ?ScopeParametersInterface $parameters = null): void
    {
        if (property_exists($modelClass, 'languageId') === false) {
            return;
        }

        $languageId = $parameters?->value('languageId');
        if ($languageId === null) {
            return;
        }

        $query->andWhere(['languageId' => $languageId]);
    }
}
