<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Hazeltree;

use Error;
use Yiisoft\ActiveRecord\ActiveRecordInterface;
use Yiisoft\ActiveRecord\UnknownPropertyException;

/**
 * Composite trait providing Hazeltree nested set for ActiveRecord models.
 *
 * This trait uses BaseHazeltreeTrait and provides the dispatch layer for
 * __get/__set/__isset/__call and AR override methods.
 *
 * Only ONE composite trait can be used per class (ElasticTrait OR HazeltreeTrait
 * OR HazeltreeElasticTrait).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait HazeltreeTrait
{
    use BaseHazeltreeTrait;

    public function __get(string $name): mixed
    {
        $result = null;
        if ($this->tryHazeltreeGet($name, $result) === false) {
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
        if ($this->tryHazeltreeSet($name, $value) === false) {
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
        if ($this->tryHazeltreeIsset($name, $result) === false && method_exists(parent::class, '__isset') === true) {
            $result = parent::__isset($name);
        }
        return $result;
    }

    public function __call(string $name, array $arguments): mixed
    {
        $result = null;
        if ($this->tryHazeltreeCall($name, $arguments, $result) === false) {
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
        } elseif ($this->hazeltreePopulateProperty($name, $value) === false) {
            parent::populateProperty($name, $value);
        }
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
