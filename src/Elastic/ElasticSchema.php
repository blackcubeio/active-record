<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Elastic;

use DateTimeInterface;
use Transliterator;
use Yiisoft\ActiveRecord\ActiveQuery;
use Yiisoft\ActiveRecord\ActiveQueryInterface;
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\ActiveRecord\ActiveRecordInterface;
use Yiisoft\ActiveRecord\Event\Handler\DefaultDateTimeOnInsert;
use Yiisoft\ActiveRecord\Event\Handler\SetDateTimeOnUpdate;
use Yiisoft\ActiveRecord\Trait\EventsTrait;

/**
 * This is the model class for table "{{%elasticSchemas}}".
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ElasticSchema extends ActiveRecord
{
    use EventsTrait;

    protected function populateProperty(string $name, mixed $value): void
    {
        if (property_exists($this, $name) === true) {
            $this->$name = $value;
        }
    }

    protected int $id;
    protected string $name = '';
    protected ?string $schema = null;
    protected ?string $view = null;

    #[DefaultDateTimeOnInsert]
    protected DateTimeInterface $dateCreate;

    #[DefaultDateTimeOnInsert]
    #[SetDateTimeOnUpdate]
    protected ?DateTimeInterface $dateUpdate = null;

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

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    public function setSchema(?string $schema): void
    {
        $this->schema = $schema;
    }

    public function getView(): ?string
    {
        return $this->view;
    }

    public function setView(?string $view): void
    {
        $this->view = $view;
    }

    public function getDateCreate(): ?DateTimeInterface
    {
        return $this->dateCreate ?? null;
    }

    public function getDateUpdate(): ?DateTimeInterface
    {
        return $this->dateUpdate;
    }

    public function tableName(): string
    {
        return '{{%elasticSchemas}}';
    }

    public static function query(ActiveRecordInterface|string|null $modelClass = null): ActiveQueryInterface
    {
        return new ActiveQuery($modelClass ?? static::class);
    }

    /**
     * Get the display view path for this schema
     *
     * @param string|null $basePath the base path to look for the view
     * @param bool $asPath if true, returns the path even if file doesn't exist
     * @return string|false the view path or false if not found
     */
    public function getDisplayView(?string $basePath, bool $asPath = false): string|false
    {
        $displayView = false;
        if ($basePath !== null) {
            $name = $this->getName();
            $view = $this->getView();

            $targetView = empty($view) === true ? $this->toUnderscore($name) : $view;
            $targetView = preg_replace('/[-_\s]+/', '_', $targetView);

            $transliterator = Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
            if ($transliterator !== null) {
                $transliterated = $transliterator->transliterate($targetView);
                if ($transliterated !== false) {
                    $targetView = $transliterated;
                }
            }

            $filePath = $basePath . '/' . $targetView . '.php';

            if ($asPath === true || file_exists($filePath) === true) {
                $displayView = $filePath;
            }
        }

        return $displayView;
    }

    /**
     * Convert CamelCase to underscore_case
     */
    private function toUnderscore(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
