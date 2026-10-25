<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Elastic;

use Blackcube\ActiveRecord\Elastic\BaseElasticTrait;
use Yiisoft\ActiveRecord\ActiveRecordInterface;

/**
 * Composite trait combining Elastic + MockHazeltree for ActiveRecord models.
 *
 * Resolves all collisions between the two traits by dispatching
 * to elastic first, then mock hazeltree, then parent::.
 *
 * Follows the same pattern as HazeltreeElasticTrait.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait CombinedElasticHazeltreeMockTrait
{
    use BaseElasticTrait;
    use MockHazeltreeTrait;

    public function __get(string $name): mixed
    {
        $result = null;
        if ($this->tryElasticGet($name, $result)) {
            return $result;
        }
        if ($this->tryMockHazeltreeGet($name, $result)) {
            return $result;
        }
        return parent::__get($name);
    }

    public function __set(string $name, mixed $value): void
    {
        if ($this->tryElasticSet($name, $value)) {
            return;
        }
        if ($this->tryMockHazeltreeSet($name, $value)) {
            return;
        }
        parent::__set($name, $value);
    }

    public function __isset(string $name): bool
    {
        $result = false;
        if ($this->tryElasticIsset($name, $result)) {
            return $result;
        }
        if ($this->tryMockHazeltreeIsset($name, $result)) {
            return $result;
        }
        return parent::__isset($name);
    }

    public function __call(string $name, array $arguments): mixed
    {
        $result = null;
        if ($this->tryElasticCall($name, $arguments, $result)) {
            return $result;
        }
        if ($this->tryMockHazeltreeCall($name, $arguments, $result)) {
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
        if ($this->elasticPopulateProperty($name, $value)) {
            return;
        }
        parent::populateProperty($name, $value);
    }

    protected function propertyValuesInternal(): array
    {
        return $this->elasticPropertyValuesInternal(parent::propertyValuesInternal());
    }

    public function populateRecord(array|object $row): static
    {
        $this->beforeElasticPopulateRecord();
        $result = parent::populateRecord($row);
        $this->afterElasticPopulateRecord();
        return $result;
    }

    protected function refreshInternal(array|ActiveRecordInterface|null $record = null): bool
    {
        $this->beforeElasticRefreshInternal();
        $result = parent::refreshInternal($record);
        $this->afterElasticRefreshInternal();
        return $result;
    }
}
