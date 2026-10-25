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

/**
 * Junction model between Owner and Label, for the via() relation.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class OwnerLabel extends ActiveRecord
{
    protected int $id;
    protected int $ownerId;
    protected int $labelId;

    public static function query(ActiveRecordInterface|string|null $modelClass = null): ActiveQueryInterface
    {
        return EachTestQuery::create($modelClass ?? static::class);
    }

    public function tableName(): string
    {
        return '{{%eagerOwnerLabels}}';
    }

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    public function setOwnerId(int $ownerId): void
    {
        $this->ownerId = $ownerId;
    }

    public function getLabelId(): int
    {
        return $this->labelId;
    }

    public function setLabelId(int $labelId): void
    {
        $this->labelId = $labelId;
    }
}
