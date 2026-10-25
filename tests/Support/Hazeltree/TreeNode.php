<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Hazeltree;

use Blackcube\ActiveRecord\Hazeltree\HazeltreeInterface;
use Blackcube\ActiveRecord\Hazeltree\HazeltreeTrait;
use Yiisoft\ActiveRecord\ActiveQueryInterface;
use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * TreeNode model for testing HazeltreeTrait.
 * Uses the composite HazeltreeTrait which handles __get/__set dispatch directly.
 *
 * @property int $id
 * @property string $name
 * @property string $path
 * @property float $left
 * @property float $right
 * @property int $level
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class TreeNode extends ActiveRecord implements HazeltreeInterface
{
    use HazeltreeTrait;

    protected int $id;
    protected string $name = '';

    public function tableName(): string
    {
        return '{{%treeNodes}}';
    }

    /**
     * Override to return TreeNodeQuery with HazeltreeQueryTrait.
     */
    public static function query($modelClass = null): ActiveQueryInterface
    {
        return new TreeNodeQuery($modelClass ?? static::class);
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
