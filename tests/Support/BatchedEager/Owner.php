<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\BatchedEager;

use Yiisoft\ActiveRecord\ActiveQueryInterface;
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\ActiveRecord\ActiveRecordInterface;
use Yiisoft\ActiveRecord\Trait\MagicRelationsTrait;

/**
 * Owner model with three relations to exercise batched eager-loading:
 * - detail   : hasOne(Detail)
 * - details  : hasMany(Detail)
 * - labels   : hasMany(Label) via the OwnerLabel junction
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Owner extends ActiveRecord
{
    use MagicRelationsTrait;

    protected int $id;
    protected string $name = '';

    public static function query(ActiveRecordInterface|string|null $modelClass = null): ActiveQueryInterface
    {
        return EachTestQuery::create($modelClass ?? static::class);
    }

    public function tableName(): string
    {
        return '{{%eagerOwners}}';
    }

    public function getDetailQuery(): ActiveQueryInterface
    {
        return $this->hasOne(Detail::class, ['ownerId' => 'id']);
    }

    public function getDetailsQuery(): ActiveQueryInterface
    {
        return $this->hasMany(Detail::class, ['ownerId' => 'id']);
    }

    public function getOwnerLabelsQuery(): ActiveQueryInterface
    {
        return $this->hasMany(OwnerLabel::class, ['ownerId' => 'id']);
    }

    public function getLabelsQuery(): ActiveQueryInterface
    {
        return $this->hasMany(Label::class, ['id' => 'labelId'])->via('ownerLabels');
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
