<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord;

use Blackcube\ActiveRecord\Elastic\BaseElasticTrait;
use Blackcube\ActiveRecord\Hazeltree\BaseHazeltreeTrait;
use Error;
use Yiisoft\ActiveRecord\ActiveRecordInterface;
use Yiisoft\ActiveRecord\UnknownPropertyException;

/**
 * Composite trait combining Elastic + Hazeltree for ActiveRecord models.
 *
 * Resolves all collisions between the two Base traits by dispatching
 * to elastic first, then hazeltree, then parent::.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait HazeltreeElasticTrait
{
    use BaseElasticTrait;
    use BaseHazeltreeTrait;

    public function __get(string $name): mixed
    {
        $result = null;
        if ($this->tryElasticGet($name, $result) === false && $this->tryHazeltreeGet($name, $result) === false) {
            if (method_exists(parent::class, '__get') === true) {
                $result = parent::__get($name);
            } else {
                throw new UnknownPropertyException('Getting unknown property or relation: ' . static::class . '::' . $name);
            }
        }
        return $result;
    }

    public function __set(string $name, mixed $value): void
    {
        if ($this->tryElasticSet($name, $value) === false && $this->tryHazeltreeSet($name, $value) === false) {
            if (method_exists(parent::class, '__set') === true) {
                parent::__set($name, $value);
            } else {
                throw new UnknownPropertyException('Setting unknown property: ' . static::class . '::' . $name);
            }
        }
    }

    public function __isset(string $name): bool
    {
        $result = false;
        if ($this->tryElasticIsset($name, $result) === false
            && $this->tryHazeltreeIsset($name, $result) === false
            && method_exists(parent::class, '__isset') === true
        ) {
            $result = parent::__isset($name);
        }
        return $result;
    }

    public function __call(string $name, array $arguments): mixed
    {
        $result = null;
        if ($this->tryElasticCall($name, $arguments, $result) === false && $this->tryHazeltreeCall($name, $arguments, $result) === false) {
            if (method_exists(parent::class, '__call') === true) {
                $result = parent::__call($name, $arguments);
            } else {
                throw new Error('Call to undefined method ' . static::class . '::' . $name . '()');
            }
        }
        return $result;
    }

    protected function populateProperty(string $name, mixed $value): void
    {
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter) === true) {
            $this->$setter($value);
        } elseif ($this->elasticPopulateProperty($name, $value) === false && $this->hazeltreePopulateProperty($name, $value) === false) {
            parent::populateProperty($name, $value);
        }
    }

    protected function propertyValuesInternal(): array
    {
        $values = parent::propertyValuesInternal();
        $values = $this->elasticPropertyValuesInternal($values);
        return $this->hazeltreePropertyValuesInternal($values);
    }

    public function populateRecord(array|object $row): static
    {
        $this->beforeElasticPopulateRecord();
        $this->beforeHazeltreePopulateRecord();
        $result = parent::populateRecord($row);
        $this->afterHazeltreePopulateRecord();
        $this->afterElasticPopulateRecord();
        return $result;
    }

    protected function refreshInternal(array|ActiveRecordInterface|null $record = null): bool
    {
        $this->beforeElasticRefreshInternal();
        $this->beforeHazeltreeRefreshInternal();
        $result = parent::refreshInternal($record);
        $this->afterHazeltreeRefreshInternal();
        $this->afterElasticRefreshInternal();
        return $result;
    }

    protected function deleteInternal(): int
    {
        return $this->hazeltreeDeleteInternal();
    }
}
