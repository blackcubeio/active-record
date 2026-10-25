<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Elastic;

/**
 * Interface for queries supporting elastic JSON virtual columns.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
interface ElasticQueryInterface
{
    /**
     * Get real DB attributes for the model class.
     *
     * @return array<string>
     */
    public function getRealAttributes(): array;
}
