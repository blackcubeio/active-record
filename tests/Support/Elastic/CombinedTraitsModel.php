<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Elastic;

use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * Test model that combines ElasticTrait with MockMagicTrait.
 * Uses CombinedElasticMockTrait (composite pattern, no MagicCompose).
 *
 * @property int|null $mockLeft
 * @property int|null $mockRight
 * @property int|null $mockLevel
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class CombinedTraitsModel extends ActiveRecord
{
    use CombinedElasticMockTrait;

    protected int $id;
    protected string $name = '';
    protected ?string $_extras = null;

    public function tableName(): string
    {
        return '{{%combinedModels}}';
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
