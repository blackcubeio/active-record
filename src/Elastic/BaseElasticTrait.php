<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Elastic;

use Blackcube\Injector\Injector;
use Psr\Log\LoggerInterface;
use Swaggest\JsonSchema\Schema;

use function is_array;
use function json_decode;
use function json_encode;

/**
 * Base trait providing elastic (JSON Schema-driven) dynamic attributes.
 *
 * All methods are prefixed with "elastic" or "tryElastic" to avoid collisions.
 * This trait is designed to be used inside a composite trait (ElasticTrait,
 * HazeltreeElasticTrait) that provides the dispatch layer (__get/__set/etc.).
 *
 * The model using this trait must have:
 * - A FK column pointing to elasticSchemas table (default: 'elasticSchemaId')
 * - A TEXT/JSON column named '_extras' to store the JSON data
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait BaseElasticTrait
{
    /**
     * @var array<string, mixed> Raw elastic values from _extras (all data, not filtered)
     */
    private array $elasticAttributeValues = [];

    /**
     * @var array<string, string> Defined attribute names from schema (lowercase => original)
     */
    private array $elasticAttributeNames = [];

    /**
     * @var bool Whether elastic schema has been parsed
     */
    private bool $elasticInitialized = false;

    /**
     * @var bool Whether _extras has been deserialized
     */
    private bool $elasticDeserialized = false;

    /**
     * @var bool Whether any elastic property has been explicitly set
     */
    private bool $elasticDirty = false;

    /**
     * @var string|null JSON storage column
     */
    protected ?string $elasticData = null;

    /**
     * @var bool System-level protection flag (toggled by populateRecord/refresh)
     */
    private bool $elasticSystemProtected = true;

    /**
     * @var bool Developer override flag (toggled by protectElastic)
     */
    private bool $elasticDevOverride = false;

    /**
     * @var mixed FK to elasticSchemas table
     */
    protected mixed $elasticSchemaId = null;

    /**
     * @var string Default JSON Schema when no schema is found
     */
    public string $defaultJsonSchema = '{"type": "object","properties": {},"required": []}';

    /**
     * @var array|null Cached schema metadata (labels, hints, placeholders)
     */
    private ?array $schemaMetadata = null;

    public function elasticColumn(): string
    {
        return '_extras';
    }

    public function elasticSchemaColumn(): string
    {
        return 'elasticSchemaId';
    }

    /**
     * Check if elastic data column is currently protected.
     */
    private function isElasticProtected(): bool
    {
        $isProtected = $this->elasticSystemProtected;
        if ($this->elasticDevOverride === true) {
            $isProtected = false;
        }
        return $isProtected;
    }

    /**
     * Allow developer to temporarily disable elastic column protection.
     *
     * @param bool $protect True to enable protection, false to disable
     */
    public function protectElastic(bool $protect): void
    {
        $this->elasticDevOverride = $protect === false;
    }

    /**
     * Internal method to toggle system protection.
     */
    private function protectElasticInternal(bool $protect): void
    {
        $this->elasticSystemProtected = $protect;
    }

    /**
     * Get parsed Swaggest Schema object.
     */
    public function getSchema(): ?Schema
    {
        $schema = null;
        $this->ensureElasticInitialized();
        $jsonSchema = $this->loadJsonSchema();

        try {
            $decoded = json_decode($jsonSchema);
            if ($decoded instanceof \stdClass === true) {
                $schema = Schema::import($decoded);
            }
        } catch (\Throwable $e) {
            $schema = null;
            if (Injector::has(LoggerInterface::class) === true) {
                Injector::get(LoggerInterface::class)->error('Elastic schema could not be parsed', [
                    'exception' => $e,
                ]);
            }
        }
        return $schema;
    }

    /**
     * Names of the properties defined by the current schema, as written in the schema.
     *
     * @return list<string>
     */
    public function getElasticAttributes(): array
    {
        $this->ensureElasticDeserialized();
        return array_values($this->elasticAttributeNames);
    }

    /**
     * Get elastic attributes as an array for validation or external use.
     * Returns only values for properties defined in the current schema.
     *
     * @return array<string, mixed>
     */
    public function getElasticValues(): array
    {
        $this->ensureElasticDeserialized();
        $values = [];
        foreach ($this->elasticAttributeNames as $originalName) {
            $values[$originalName] = $this->elasticAttributeValues[$originalName] ?? null;
        }
        return $values;
    }

    protected function getSchemaMetadata(): array
    {
        if ($this->schemaMetadata === null && ($schema = $this->getSchema()) !== null) {
            $this->schemaMetadata = [
                'labels' => [],
                'hints' => [],
                'placeholders' => [],
            ];
            $properties = $schema->getProperties();
            foreach ($properties as $name => $property) {
                if (isset($property->title) === true) {
                    $this->schemaMetadata['labels'][$name] = $property->title;
                }
                if (isset($property->description) === true) {
                    $this->schemaMetadata['hints'][$name] = $property->description;
                }
                if (isset($property->placeholder) === true) {
                    $this->schemaMetadata['placeholders'][$name] = $property->placeholder;
                }
            }
        }
        return $this->schemaMetadata;
    }

    /**
     * @return array<string, string>
     */
    public function getElasticLabels(): array
    {
        $schemaMetadata = $this->getSchemaMetadata();
        return $schemaMetadata['labels'];
    }

    /**
     * @return array<string, string>
     */
    public function getElasticHints(): array
    {
        $schemaMetadata = $this->getSchemaMetadata();
        return $schemaMetadata['hints'];
    }

    /**
     * @return array<string, string>
     */
    public function getElasticPlaceholders(): array
    {
        $schemaMetadata = $this->getSchemaMetadata();
        return $schemaMetadata['placeholders'];
    }

    /**
     * Get property label with fallback chain: model -> elastic -> generated.
     */
    public function getPropertyLabel(string $property): string
    {
        $label = null;
        if (method_exists($this, 'getPropertyLabels') === true) {
            $modelLabels = $this->getPropertyLabels();
            if (isset($modelLabels[$property]) === true) {
                $label = $modelLabels[$property];
            }
        }

        if ($label === null) {
            $elasticLabels = $this->getElasticLabels();
            if (isset($elasticLabels[$property]) === true) {
                $label = $elasticLabels[$property];
            }
        }

        if ($label === null) {
            $label = $this->generatePropertyLabel($property);
        }

        return $label;
    }

    /**
     * Get property hint with fallback chain: model -> elastic -> empty.
     */
    public function getPropertyHint(string $property): string
    {
        $hint = null;
        if (method_exists($this, 'getPropertyHints') === true) {
            $modelHints = $this->getPropertyHints();
            if (isset($modelHints[$property]) === true) {
                $hint = $modelHints[$property];
            }
        }

        if ($hint === null) {
            $elasticHints = $this->getElasticHints();
            if (isset($elasticHints[$property]) === true) {
                $hint = $elasticHints[$property];
            }
        }

        if ($hint === null) {
            $hint = '';
        }

        return $hint;
    }

    /**
     * Get property placeholder with fallback chain: model -> elastic -> empty.
     */
    public function getPropertyPlaceholder(string $property): string
    {
        $placeholder = null;
        if (method_exists($this, 'getPropertyPlaceholders') === true) {
            $modelPlaceholders = $this->getPropertyPlaceholders();
            if (isset($modelPlaceholders[$property]) === true) {
                $placeholder = $modelPlaceholders[$property];
            }
        }

        if ($placeholder === null) {
            $elasticPlaceholders = $this->getElasticPlaceholders();
            if (isset($elasticPlaceholders[$property]) === true) {
                $placeholder = $elasticPlaceholders[$property];
            }
        }

        if ($placeholder === null) {
            $placeholder = '';
        }

        return $placeholder;
    }

    private function generatePropertyLabel(string $property): string
    {
        return ucfirst(
            preg_replace(
                '/(?<![A-Z])[A-Z]/',
                ' \0',
                $property
            ) ?? $property
        );
    }

    private function setElasticData(string|array|null $extras = null): void
    {
        if ($extras === null || is_string($extras) === true) {
            $this->elasticData = $extras;
            $this->elasticDeserialized = false;
            $this->elasticDirty = false;
        } else {
            $this->ensureElasticDeserialized();
            foreach ($extras as $name => $value) {
                $this->elasticAttributeValues[$name] = $value;
                $this->elasticDirty = true;
            }
        }
    }

    /**
     * Canonical elastic property name, case-insensitive lookup.
     * @return string|null Original property name or null if not found
     */
    private function elasticNameFor(string $name): ?string
    {
        return $this->elasticAttributeNames[strtolower($name)] ?? null;
    }

    /**
     * Try to get an elastic property value.
     * @return bool true if handled
     */
    protected function tryElasticGet(string $name, mixed &$result): bool
    {
        $isHandled = false;
        if ($name === $this->elasticColumn()) {
            $this->ensureElasticDeserialized();
            $result = $this->serializeElastic();
            $isHandled = true;
        } elseif ($name === $this->elasticSchemaColumn()) {
            $result = $this->elasticSchemaId;
            $isHandled = true;
        } else {
            $this->ensureElasticDeserialized();
            $elasticName = $this->elasticNameFor($name);
            if ($elasticName !== null) {
                $result = $this->elasticAttributeValues[$elasticName] ?? null;
                $isHandled = true;
            }
        }

        return $isHandled;
    }

    /**
     * Try to set an elastic property value.
     * @return bool true if handled
     */
    protected function tryElasticSet(string $name, mixed $value): bool
    {
        $isHandled = false;
        if ($name === $this->elasticColumn()) {
            if ($this->isElasticProtected() === true) {
                throw new \Error(
                    sprintf('Cannot set read-only elastic column %s::$%s. Use individual properties instead.', static::class, $name)
                );
            }
            $this->setElasticData($value);
            $isHandled = true;
        } elseif ($name === $this->elasticSchemaColumn()) {
            $this->setElasticSchemaIdInternal($value);
            $isHandled = true;
        } else {
            $this->ensureElasticDeserialized();
            $this->ensureElasticInitialized();
            $elasticName = $this->elasticNameFor($name);
            if ($elasticName !== null) {
                $this->elasticAttributeValues[$elasticName] = $value;
                $this->elasticDirty = true;
                $isHandled = true;
            }
        }

        return $isHandled;
    }

    /**
     * Try to check if an elastic property is set.
     * @return bool true if handled
     */
    protected function tryElasticIsset(string $name, bool &$result): bool
    {
        $isHandled = false;
        if ($name === $this->elasticColumn()) {
            $result = $this->elasticData !== null;
            $isHandled = true;
        } elseif ($name === $this->elasticSchemaColumn()) {
            $result = $this->elasticSchemaId !== null;
            $isHandled = true;
        } else {
            $this->ensureElasticDeserialized();
            $elasticName = $this->elasticNameFor($name);
            if ($elasticName !== null) {
                $result = isset($this->elasticAttributeValues[$elasticName]) === true;
                $isHandled = true;
            }
        }

        return $isHandled;
    }

    /**
     * Try to handle a magic method call for elastic properties.
     * @return bool true if handled
     */
    protected function tryElasticCall(string $name, array $arguments, mixed &$result): bool
    {
        $isHandled = false;
        if (str_starts_with($name, 'get') === true && strlen($name) > 3) {
            if ($name === 'get' . ucfirst($this->elasticColumn())) {
                $this->ensureElasticDeserialized();
                $result = $this->serializeElastic();
                $isHandled = true;
            } elseif ($name === 'get' . ucfirst($this->elasticSchemaColumn())) {
                $result = $this->elasticSchemaId;
                $isHandled = true;
            } else {
                $property = lcfirst(substr($name, 3));
                $this->ensureElasticDeserialized();
                $elasticName = $this->elasticNameFor($property);
                if ($elasticName !== null) {
                    $result = $this->elasticAttributeValues[$elasticName] ?? null;
                    $isHandled = true;
                }
            }
        }

        if ($isHandled === false && str_starts_with($name, 'set') === true && strlen($name) > 3) {
            if ($name === 'set' . ucfirst($this->elasticColumn())) {
                if ($this->isElasticProtected() === true) {
                    throw new \Error(
                        sprintf('Cannot set read-only elastic column %s::$%s. Use individual properties instead.', static::class, $this->elasticColumn())
                    );
                }
                if (is_string($arguments[0]) === false) {
                    $this->elasticData = json_encode($arguments[0]);
                } else {
                    $this->elasticData = $arguments[0];
                }
                $this->elasticDeserialized = false;
                $result = null;
                $isHandled = true;
            } elseif ($name === 'set' . ucfirst($this->elasticSchemaColumn())) {
                $this->setElasticSchemaIdInternal($arguments[0] ?? null);
                $result = null;
                $isHandled = true;
            } else {
                $property = lcfirst(substr($name, 3));
                $this->ensureElasticDeserialized();
                $this->ensureElasticInitialized();
                $elasticName = $this->elasticNameFor($property);
                if ($elasticName !== null) {
                    $this->elasticAttributeValues[$elasticName] = $arguments[0] ?? null;
                    $this->elasticDirty = true;
                    $result = null;
                    $isHandled = true;
                }
            }
        }

        return $isHandled;
    }

    /**
     * Include elastic columns in property values for ActiveRecord save.
     *
     * @param array $values Current property values from parent
     * @return array Merged values including elastic columns
     */
    protected function elasticPropertyValuesInternal(array $values): array
    {
        $this->ensureElasticDeserialized();
        $values[$this->elasticColumn()] = $this->serializeElastic();
        $values[$this->elasticSchemaColumn()] = $this->elasticSchemaId;
        return $values;
    }

    /**
     * Try to populate an elastic property.
     * @return bool true if handled
     */
    protected function elasticPopulateProperty(string $name, mixed $value): bool
    {
        $isHandled = false;
        if ($name === $this->elasticColumn()) {
            if ($this->isElasticProtected() === true) {
                throw new \Error(
                    sprintf('Cannot set read-only elastic column %s::$%s. Use individual properties instead.', static::class, $name)
                );
            }
            $this->setElasticData($value);
            $isHandled = true;
        } elseif ($name === $this->elasticSchemaColumn()) {
            $this->setElasticSchemaIdInternal($value);
            $isHandled = true;
        }

        return $isHandled;
    }

    /**
     * Disable protection before populateRecord.
     */
    protected function beforeElasticPopulateRecord(): void
    {
        $this->protectElasticInternal(false);
    }

    /**
     * Re-enable protection after populateRecord.
     */
    protected function afterElasticPopulateRecord(): void
    {
        $this->protectElasticInternal(true);
    }

    /**
     * Disable protection before refreshInternal.
     */
    protected function beforeElasticRefreshInternal(): void
    {
        $this->protectElasticInternal(false);
    }

    /**
     * Re-enable protection after refreshInternal.
     */
    protected function afterElasticRefreshInternal(): void
    {
        $this->protectElasticInternal(true);
    }

    /**
     * Clear static schema cache.
     */
    public static function clearSchemaCache(): void
    {
        ElasticSchemaCache::clearSchemaCache();
    }

    private function setElasticSchemaIdInternal(mixed $elasticSchemaId): void
    {
        $this->elasticSchemaId = $elasticSchemaId;
        $this->elasticInitialized = false;
    }

    private function ensureElasticInitialized(): void
    {
        if ($this->elasticInitialized === false) {
            $jsonSchema = $this->loadJsonSchema();
            $this->parseJsonSchema($jsonSchema);
            $this->elasticInitialized = true;
        }
    }

    private function loadJsonSchema(): string
    {
        $jsonSchema = $this->defaultJsonSchema;
        if ($this->elasticSchemaId !== null) {
            $jsonSchema = ElasticSchemaCache::getCachedJsonSchema((int) $this->elasticSchemaId) ?? $this->defaultJsonSchema;
        }

        return $jsonSchema;
    }

    private function parseJsonSchema(string $jsonSchema): void
    {
        if ($this->elasticSchemaId !== null) {
            $this->elasticAttributeNames = ElasticSchemaCache::getCachedAttributeNames((int) $this->elasticSchemaId);
        } else {
            $this->elasticAttributeNames = [];
            $properties = null;
            $decoded = json_decode($jsonSchema);
            if ($decoded instanceof \stdClass === true) {
                $properties = $decoded->properties ?? null;
            }
            if ($properties !== null) {
                foreach ($properties as $name => $property) {
                    $this->elasticAttributeNames[strtolower($name)] = $name;
                }
            }
        }
    }

    private function ensureElasticDeserialized(): void
    {
        if ($this->elasticDeserialized === false) {
            $this->ensureElasticInitialized();

            $extras = $this->elasticData ?? null;
            if ($extras !== null && $extras !== '') {
                $decoded = is_array($extras) === true ? $extras : json_decode($extras, true);
                if (is_array($decoded) === true) {
                    foreach ($decoded as $name => $value) {
                        $this->elasticAttributeValues[$name] = $value;
                    }
                }
            }

            $this->elasticDeserialized = true;
        }
    }

    private function serializeElastic(): ?string
    {
        if ($this->elasticDirty === false) {
            $serialized = $this->elasticData;
        } elseif ($this->elasticSchemaId === null) {
            $serialized = json_encode($this->elasticAttributeValues);
        } else {
            $this->ensureElasticInitialized();

            $filtered = [];
            foreach ($this->elasticAttributeNames as $originalName) {
                if (array_key_exists($originalName, $this->elasticAttributeValues) === true) {
                    $filtered[$originalName] = $this->elasticAttributeValues[$originalName];
                }
            }

            $serialized = json_encode($filtered);
        }

        return $serialized;
    }
}
