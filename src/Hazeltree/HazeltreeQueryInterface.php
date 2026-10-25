<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Hazeltree;

/**
 * Interface for queries supporting tree navigation.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
interface HazeltreeQueryInterface
{
    public function forNode(HazeltreeInterface $node): static;

    public function roots(): static;

    public function children(): static;

    public function parent(): static;

    public function siblings(): static;

    public function excludingSelf(): static;

    public function excludingDescendants(): static;

    public function next(): static;

    public function previous(): static;

    public function includeDescendants(): static;

    public function includeAncestors(): static;

    public function includeSelf(): static;

    public function natural(): static;

    public function reverse(): static;
}
