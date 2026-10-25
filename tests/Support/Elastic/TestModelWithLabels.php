<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Elastic;

use Blackcube\ActiveRecord\Elastic\ElasticTrait;
use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * Test model using ElasticTrait with model-defined labels.
 * Used to test priority: model labels > elastic labels > generated.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class TestModelWithLabels extends ActiveRecord
{
    use ElasticTrait;

    protected int $id;
    protected string $title = '';

    public function tableName(): string
    {
        return '{{%testModels}}';
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

    /**
     * Model-defined labels - these should take priority over elastic labels.
     */
    public function getPropertyLabels(): array
    {
        return [
            'subject' => 'Model-defined label',
        ];
    }

    /**
     * Model-defined hints - these should take priority over elastic hints.
     */
    public function getPropertyHints(): array
    {
        return [
            'subject' => 'Model-defined hint',
        ];
    }

    /**
     * Model-defined placeholders - these should take priority over elastic placeholders.
     */
    public function getPropertyPlaceholders(): array
    {
        return [
            'subject' => 'Model-defined placeholder',
        ];
    }
}
