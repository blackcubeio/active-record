<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Hazeltree;

use Blackcube\ActiveRecord\Hazeltree\BaseHazeltreeQueryTrait;
use Blackcube\ActiveRecord\Hazeltree\HazeltreeInterface;
use Yiisoft\ActiveRecord\ActiveQuery;
use Yiisoft\ActiveRecord\ActiveQueryInterface;
use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * Test model that combines HazeltreeTrait with MockElasticTrait.
 * Uses the composite HazeltreeMockElasticTrait which dispatches to both.
 *
 * @property string|null $title
 * @property string|null $description
 * @property float|null $price
 * @property bool|null $active
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class CombinedTraitsModel extends ActiveRecord implements HazeltreeInterface
{
    use HazeltreeMockElasticTrait;

    protected int $id;
    protected string $name = '';

    public function tableName(): string
    {
        return 'combinedModels';
    }

    public static function query(\Yiisoft\ActiveRecord\ActiveRecordInterface|string|null $modelClass = null): ActiveQueryInterface
    {
        return new CombinedTraitsQuery($modelClass ?? static::class);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}

/**
 * Query class for CombinedTraitsModel with Hazeltree support.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class CombinedTraitsQuery extends ActiveQuery
{
    use BaseHazeltreeQueryTrait;
}
