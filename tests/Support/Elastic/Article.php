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
 * Article model for integration testing with custom elastic column name.
 *
 * @property int $id
 * @property string $title
 * @property int|null $elasticSchemaId
 * @property string|null $_data
 *
 * Elastic properties (from schema):
 * @property string $author
 * @property int|null $rating
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Article extends ActiveRecord
{
    use ElasticTrait;

    public static function query(ActiveRecordInterface|string|null $modelClass = null): ActiveQueryInterface
    {
        return TestModelQuery::create($modelClass ?? static::class);
    }

    protected int $id;
    protected string $title = '';

    public function elasticColumn(): string
    {
        return '_data';
    }

    public function tableName(): string
    {
        return '{{%articles}}';
    }

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
