<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord;

use Yiisoft\ActiveRecord\ActiveQueryInterface;

/**
 * Interface for queries supporting dynamic scope registration.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
interface ScopableQueryInterface extends ActiveQueryInterface
{
    /**
     * @param class-string<ScopeInterface> $scopeClass
     */
    public function addScope(string $scopeClass): static;

    /**
     * @param class-string<ScopeInterface> $scopeClass
     */
    public function removeScope(string $scopeClass): static;

    /**
     * @param class-string<ScopeInterface> $scopeClass
     */
    public function hasScope(string $scopeClass): bool;

    public function setState(string $key, mixed $value): static;

    public function getState(string $key, mixed $default = null): mixed;
}
