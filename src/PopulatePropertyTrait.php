<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord;

/**
 * Standalone setter-first populateProperty override.
 *
 * For models that only need setter-first dispatch (no Elastic/Hazeltree).
 * Models using ElasticTrait, HazeltreeTrait, or HazeltreeElasticTrait
 * already have setter-first built into their populateProperty — do not
 * combine this trait with those.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait PopulatePropertyTrait
{
    protected function populateProperty(string $name, mixed $value): void
    {
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter) === true) {
            $this->$setter($value);
        } else {
            parent::populateProperty($name, $value);
        }
    }
}
