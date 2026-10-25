<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Hazeltree;

use Blackcube\ActiveRecord\Hazeltree\BaseHazeltreeTrait;
use Yiisoft\ActiveRecord\ActiveRecordInterface;

/**
 * Composite trait combining BaseHazeltreeTrait + BaseMockElasticTrait for ActiveRecord models.
 * Same pattern as HazeltreeElasticTrait in the main package.
 *
 * Dispatches: mock elastic first, then hazeltree, then parent::.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait HazeltreeMockElasticTrait
{
    use BaseMockElasticTrait;
    use BaseHazeltreeTrait;

    public function __get(string $name): mixed
    {
        $result = null;
        if ($this->tryMockElasticGet($name, $result)) {
            return $result;
        }
        if ($this->tryHazeltreeGet($name, $result)) {
            return $result;
        }
        return parent::__get($name);
    }

    public function __set(string $name, mixed $value): void
    {
        if ($this->tryMockElasticSet($name, $value)) {
            return;
        }
        if ($this->tryHazeltreeSet($name, $value)) {
            return;
        }
        parent::__set($name, $value);
    }

    public function __isset(string $name): bool
    {
        $result = false;
        if ($this->tryMockElasticIsset($name, $result)) {
            return $result;
        }
        if ($this->tryHazeltreeIsset($name, $result)) {
            return $result;
        }
        return parent::__isset($name);
    }

    public function __call(string $name, array $arguments): mixed
    {
        $result = null;
        if ($this->tryMockElasticCall($name, $arguments, $result)) {
            return $result;
        }
        if ($this->tryHazeltreeCall($name, $arguments, $result)) {
            return $result;
        }
        return parent::__call($name, $arguments);
    }

    protected function populateProperty(string $name, mixed $value): void
    {
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter) === true) {
            $this->$setter($value);
            return;
        }
        if ($this->hazeltreePopulateProperty($name, $value)) {
            return;
        }
        parent::populateProperty($name, $value);
    }

    protected function propertyValuesInternal(): array
    {
        return $this->hazeltreePropertyValuesInternal(parent::propertyValuesInternal());
    }

    public function populateRecord(array|object $row): static
    {
        $this->beforeHazeltreePopulateRecord();
        $result = parent::populateRecord($row);
        $this->afterHazeltreePopulateRecord();
        return $result;
    }

    protected function refreshInternal(array|ActiveRecordInterface|null $record = null): bool
    {
        $this->beforeHazeltreeRefreshInternal();
        $result = parent::refreshInternal($record);
        $this->afterHazeltreeRefreshInternal();
        return $result;
    }

    protected function deleteInternal(): int
    {
        return $this->hazeltreeDeleteInternal();
    }
}
