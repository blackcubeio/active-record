<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord;

/**
 * Contract for named query scopes.
 *
 * Each scope carries its own name via name() and applies
 * its logic via process() with a uniform signature.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
interface ScopeInterface
{
    /**
     * Scope name used as method name via __call dispatch.
     */
    public static function name(): string;

    /**
     * Apply the scope to the query.
     */
    public function process(ScopableQueryInterface $query, string $modelClass, ?ScopeParametersInterface $parameters = null): void;
}
