<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Elastic;

/**
 * Mock trait that implements magic methods using the try* pattern.
 * Simulates another trait (like HazeltreeTrait) that would coexist with ElasticTrait.
 *
 * Uses the same try* convention as BaseElasticTrait/BaseHazeltreeTrait:
 * - tryMockGet(string $name, mixed &$result): bool
 * - tryMockSet(string $name, mixed $value): bool
 * - tryMockIsset(string $name, bool &$result): bool
 * - tryMockCall(string $name, array $arguments, mixed &$result): bool
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait MockMagicTrait
{
    private ?int $mockLeft = null;
    private ?int $mockRight = null;
    private ?int $mockLevel = null;

    private static array $mockProperties = [
        'mockLeft',
        'mockRight',
        'mockLevel',
    ];

    private function isMockProperty(string $name): bool
    {
        return in_array($name, self::$mockProperties, true);
    }

    /**
     * Try to get a mock property value.
     * @return bool true if handled
     */
    protected function tryMockGet(string $name, mixed &$result): bool
    {
        if ($this->isMockProperty($name) === false) {
            return false;
        }
        $result = $this->$name;
        return true;
    }

    /**
     * Try to set a mock property value.
     * @return bool true if handled
     */
    protected function tryMockSet(string $name, mixed $value): bool
    {
        if ($this->isMockProperty($name) === false) {
            return false;
        }
        $this->$name = $value !== null ? (int) $value : null;
        return true;
    }

    /**
     * Try to check if a mock property is set.
     * @return bool true if handled
     */
    protected function tryMockIsset(string $name, bool &$result): bool
    {
        if ($this->isMockProperty($name) === false) {
            return false;
        }
        $result = $this->$name !== null;
        return true;
    }

    /**
     * Try to handle a magic method call for mock properties.
     * @return bool true if handled
     */
    protected function tryMockCall(string $name, array $arguments, mixed &$result): bool
    {
        if (str_starts_with($name, 'get') === true && strlen($name) > 3) {
            $property = lcfirst(substr($name, 3));
            if ($this->isMockProperty($property)) {
                $result = $this->$property;
                return true;
            }
        }

        if (str_starts_with($name, 'set') === true && strlen($name) > 3) {
            $property = lcfirst(substr($name, 3));
            if ($this->isMockProperty($property)) {
                $value = $arguments[0] ?? null;
                $this->$property = $value !== null ? (int) $value : null;
                $result = null;
                return true;
            }
        }

        if ($name === 'isLeaf') {
            $result = ($this->mockRight - $this->mockLeft) === 1;
            return true;
        }

        return false;
    }
}
