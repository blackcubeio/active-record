<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Hazeltree;

use Blackcube\ActiveRecord\Hazeltree\BaseHazeltreeTrait;
use Blackcube\ActiveRecord\Hazeltree\HazeltreeInterface;

/**
 * Node class extending a parent with magic methods for testing parent delegation.
 * This tests that BaseHazeltreeTrait correctly delegates to parent::__get(), etc.
 * Uses BaseHazeltreeTrait with explicit dispatch that falls through to parent.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class NodeWithParentMagic extends ParentWithMagicMethods implements HazeltreeInterface
{
    use BaseHazeltreeTrait;

    public string $name = '';

    public function __get(string $name): mixed
    {
        $result = null;
        if ($this->tryHazeltreeGet($name, $result)) {
            return $result;
        }
        return parent::__get($name);
    }

    public function __set(string $name, mixed $value): void
    {
        if ($this->tryHazeltreeSet($name, $value)) {
            return;
        }
        parent::__set($name, $value);
    }

    public function __isset(string $name): bool
    {
        $result = false;
        if ($this->tryHazeltreeIsset($name, $result)) {
            return $result;
        }
        return parent::__isset($name);
    }

    public function __call(string $name, array $arguments): mixed
    {
        $result = null;
        if ($this->tryHazeltreeCall($name, $arguments, $result)) {
            return $result;
        }
        return parent::__call($name, $arguments);
    }

    public function tableName(): string
    {
        return 'nodes_with_parent_magic';
    }

    public static function primaryKey(): array
    {
        return ['id'];
    }
}
