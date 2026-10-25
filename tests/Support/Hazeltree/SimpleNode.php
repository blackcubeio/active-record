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
 * SimpleNode for testing HazeltreeTrait magic methods without ActiveRecord parent.
 * Uses BaseHazeltreeTrait with custom __get/__set/__isset dispatch
 * because the composite HazeltreeTrait calls parent:: which doesn't exist here.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class SimpleNode implements HazeltreeInterface
{
    use BaseHazeltreeTrait;

    public string $name = '';

    public function __get(string $name): mixed
    {
        $result = null;
        if ($this->tryHazeltreeGet($name, $result)) {
            return $result;
        }
        return null;
    }

    public function __set(string $name, mixed $value): void
    {
        if ($this->tryHazeltreeSet($name, $value)) {
            return;
        }
    }

    public function __isset(string $name): bool
    {
        $result = false;
        if ($this->tryHazeltreeIsset($name, $result)) {
            return $result;
        }
        return false;
    }

    public function __call(string $name, array $arguments): mixed
    {
        $result = null;
        if ($this->tryHazeltreeCall($name, $arguments, $result)) {
            return $result;
        }
        throw new \Error(sprintf('Call to undefined method %s::%s()', static::class, $name));
    }

    public function tableName(): string
    {
        return 'simple_nodes';
    }

    public static function primaryKey(): array
    {
        return ['id'];
    }
}
