<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord;

use BadMethodCallException;

/**
 * Dynamic scope registration for ActiveQuery classes.
 *
 * Usage:
 *   $query->addScope(ActiveScope::class)
 *         ->addScope(LanguageScope::class);
 *   $query->active(active: true);    // dispatched via __call
 *   $query->language(languageId: 'fr');
 *
 * PHP 8 named arguments in __call:
 *   $query->active(active: true) → __call('active', ['active' => true])
 *   → ScopeParameters(['active' => true])
 *   → scope->process($query, $modelClass, $parameters)
 *
 * Shared state between scopes uses setState() / getState().
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait ScopableTrait
{
    /** @var array<string, ScopeInterface> */
    private array $scopes = [];

    /** @var array<string, mixed> */
    private array $state = [];

    /**
     * Register a named scope from its class.
     *
     * @param class-string<ScopeInterface> $scopeClass
     */
    public function addScope(string $scopeClass): static
    {
        $this->scopes[$scopeClass::name()] = new $scopeClass();
        return $this;
    }

    /**
     * Remove a previously registered scope.
     *
     * @param class-string<ScopeInterface> $scopeClass
     */
    public function removeScope(string $scopeClass): static
    {
        unset($this->scopes[$scopeClass::name()]);
        return $this;
    }

    /**
     * Check if a scope is registered.
     *
     * @param class-string<ScopeInterface> $scopeClass
     */
    public function hasScope(string $scopeClass): bool
    {
        return isset($this->scopes[$scopeClass::name()]) === true;
    }

    /**
     * Store shared state accessible by any scope.
     */
    public function setState(string $key, mixed $value): static
    {
        $this->state[$key] = $value;
        return $this;
    }

    /**
     * Read shared state.
     */
    public function getState(string $key, mixed $default = null): mixed
    {
        return $this->state[$key] ?? $default;
    }

    /**
     * Dispatch to registered scope or fail.
     *
     * Named arguments are preserved by PHP 8 __call:
     *   $query->active(active: true)
     *   → __call('active', ['active' => true])
     *   → new ScopeParameters(['active' => true])
     *
     * @throws BadMethodCallException if no scope matches
     */
    public function __call(string $name, array $params): static
    {
        if (isset($this->scopes[$name]) === true) {
            $parameters = empty($params) === false ? new ScopeParameters($params) : null;
            $this->scopes[$name]->process($this, $this->getModel()::class, $parameters);
            return $this;
        }

        throw new BadMethodCallException(
            sprintf('Unknown scope or method: %s::%s()', static::class, $name)
        );
    }
}
