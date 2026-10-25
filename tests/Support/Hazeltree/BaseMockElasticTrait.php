<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Hazeltree;

/**
 * Base mock trait that simulates ElasticTrait for testing trait composition.
 * Uses the try* pattern (like BaseElasticTrait / BaseHazeltreeTrait).
 *
 * Properties: title, description, price, active.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait BaseMockElasticTrait
{
    private array $elasticData = [];

    private static array $elasticProperties = [
        'title',
        'description',
        'price',
        'active',
    ];

    private function isElasticProperty(string $name): bool
    {
        return in_array($name, self::$elasticProperties, true);
    }

    /**
     * Try to get a mock elastic property value.
     * @return bool true if handled
     */
    protected function tryMockElasticGet(string $name, mixed &$result): bool
    {
        if ($this->isElasticProperty($name) === false) {
            return false;
        }
        $result = $this->elasticData[$name] ?? null;
        return true;
    }

    /**
     * Try to set a mock elastic property value.
     * @return bool true if handled
     */
    protected function tryMockElasticSet(string $name, mixed $value): bool
    {
        if ($this->isElasticProperty($name) === false) {
            return false;
        }
        $this->elasticData[$name] = $value;
        return true;
    }

    /**
     * Try to check if a mock elastic property is set.
     * @return bool true if handled
     */
    protected function tryMockElasticIsset(string $name, bool &$result): bool
    {
        if ($this->isElasticProperty($name) === false) {
            return false;
        }
        $result = isset($this->elasticData[$name]);
        return true;
    }

    /**
     * Try to handle a magic method call for mock elastic properties.
     * @return bool true if handled
     */
    protected function tryMockElasticCall(string $name, array $arguments, mixed &$result): bool
    {
        if (str_starts_with($name, 'get') === true && strlen($name) > 3) {
            $property = lcfirst(substr($name, 3));
            if ($this->isElasticProperty($property)) {
                $result = $this->elasticData[$property] ?? null;
                return true;
            }
        }

        if (str_starts_with($name, 'set') === true && strlen($name) > 3 && count($arguments) === 1) {
            $property = lcfirst(substr($name, 3));
            if ($this->isElasticProperty($property)) {
                $this->elasticData[$property] = $arguments[0];
                $result = null;
                return true;
            }
        }

        return false;
    }

    public function getElasticData(): array
    {
        return $this->elasticData;
    }
}
