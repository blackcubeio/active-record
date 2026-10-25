<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Elastic;

use Blackcube\ActiveRecord\Elastic\ElasticTrait;
use Yiisoft\ActiveRecord\ActiveQueryInterface;
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\ActiveRecord\ActiveRecordInterface;

/**
 * Product model for integration testing with custom column names.
 * - data instead of _extras
 * - sid instead of elasticSchemaId
 *
 * @property int $id
 * @property string $name
 * @property int|null $sid
 * @property string|null $data
 *
 * Elastic properties (from schema):
 * @property string $sku
 * @property float|null $price
 * @property bool|null $inStock
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Product extends ActiveRecord
{
    use ElasticTrait;

    public static function query(ActiveRecordInterface|string|null $modelClass = null): ActiveQueryInterface
    {
        return TestModelQuery::create($modelClass ?? static::class);
    }

    protected int $id;
    protected string $name = '';

    public function elasticColumn(): string
    {
        return 'data';
    }

    public function elasticSchemaColumn(): string
    {
        return 'sid';
    }

    public function tableName(): string
    {
        return '{{%products}}';
    }

    public function getId(): ?int
    {
        return $this->id ?? null;
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
