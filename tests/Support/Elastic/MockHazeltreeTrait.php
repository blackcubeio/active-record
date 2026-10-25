<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Elastic;

/**
 * Mock trait simulating HazeltreeTrait for testing trait composition.
 * Uses the try* pattern (no MagicCompose).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait MockHazeltreeTrait
{
    private ?float $mockLeft = null;
    private ?float $mockRight = null;
    private ?int $mockLevel = null;

    private function isMockHazeltreeProperty(string $name): bool
    {
        return in_array($name, [
            'mockLeft',
            'mockRight',
            'mockLevel',
        ], true);
    }

    /**
     * Try to get a mock hazeltree property value.
     * @return bool true if handled
     */
    protected function tryMockHazeltreeGet(string $name, mixed &$result): bool
    {
        if ($this->isMockHazeltreeProperty($name) === false) {
            return false;
        }
        $result = match ($name) {
            'mockLeft' => $this->mockLeft,
            'mockRight' => $this->mockRight,
            'mockLevel' => $this->mockLevel,
            default => null,
        };
        return true;
    }

    /**
     * Try to set a mock hazeltree property value.
     * @return bool true if handled
     */
    protected function tryMockHazeltreeSet(string $name, mixed $value): bool
    {
        if ($this->isMockHazeltreeProperty($name) === false) {
            return false;
        }
        match ($name) {
            'mockLeft' => $this->mockLeft = $value,
            'mockRight' => $this->mockRight = $value,
            'mockLevel' => $this->mockLevel = $value,
        };
        return true;
    }

    /**
     * Try to check if a mock hazeltree property is set.
     * @return bool true if handled
     */
    protected function tryMockHazeltreeIsset(string $name, bool &$result): bool
    {
        if ($this->isMockHazeltreeProperty($name) === false) {
            return false;
        }
        $result = match ($name) {
            'mockLeft' => $this->mockLeft !== null,
            'mockRight' => $this->mockRight !== null,
            'mockLevel' => $this->mockLevel !== null,
            default => false,
        };
        return true;
    }

    /**
     * Try to handle a magic method call for mock hazeltree properties.
     * @return bool true if handled
     */
    protected function tryMockHazeltreeCall(string $name, array $arguments, mixed &$result): bool
    {
        if (str_starts_with($name, 'get') === true && strlen($name) > 3) {
            $property = lcfirst(substr($name, 3));
            if ($this->isMockHazeltreeProperty($property)) {
                $this->tryMockHazeltreeGet($property, $result);
                return true;
            }
        }

        if (str_starts_with($name, 'set') === true && strlen($name) > 3 && count($arguments) === 1) {
            $property = lcfirst(substr($name, 3));
            if ($this->isMockHazeltreeProperty($property)) {
                $this->tryMockHazeltreeSet($property, $arguments[0]);
                $result = null;
                return true;
            }
        }

        if ($name === 'isLeaf') {
            $result = (int) ($this->mockRight - $this->mockLeft) === 1;
            return true;
        }

        return false;
    }
}
