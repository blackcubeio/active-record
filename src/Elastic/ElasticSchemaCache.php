<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Elastic;

/**
 * Static cache for elastic schemas.
 * Provides shared schema caching across all elastic-enabled models
 * (one query, one parse per schemaId).
 *
 * Extracted from the former BaseElastic abstract class.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ElasticSchemaCache
{
    /** @var array<int, string|null>|null Schema JSON strings keyed by schemaId */
    private static ?array $jsonSchemaCache = null;

    /** @var array<int, array<string, string>>|null Parsed attribute names keyed by schemaId */
    private static ?array $attributeNamesCache = null;

    public static function preloadAllSchemas(): void
    {
        if (self::$jsonSchemaCache === null) {
            self::$jsonSchemaCache = [];
            self::$attributeNamesCache = [];

            $schemasQuery = ElasticSchema::query();
            /** @var ElasticSchema $schema */
            foreach ($schemasQuery->each() as $schema) {
                self::$jsonSchemaCache[$schema->getId()] = $schema->getSchema();
            }
        }
    }

    public static function getCachedJsonSchema(int $schemaId): ?string
    {
        self::preloadAllSchemas();
        return self::$jsonSchemaCache[$schemaId] ?? null;
    }

    /**
     * @return array<string, string> lowercase => original name
     */
    public static function getCachedAttributeNames(int $schemaId): array
    {
        self::preloadAllSchemas();
        if (isset(self::$attributeNamesCache[$schemaId]) === false) {
            $json = self::$jsonSchemaCache[$schemaId] ?? null;
            self::$attributeNamesCache[$schemaId] = $json !== null ? self::parseAttributeNames($json) : [];
        }
        return self::$attributeNamesCache[$schemaId];
    }

    /**
     * @return array<string, string> lowercase => original name
     */
    private static function parseAttributeNames(string $jsonSchema): array
    {
        $names = [];
        $properties = null;
        $decoded = json_decode($jsonSchema);
        if ($decoded instanceof \stdClass === true) {
            $properties = $decoded->properties ?? null;
        }
        if ($properties !== null) {
            foreach ($properties as $name => $property) {
                $names[strtolower($name)] = $name;
            }
        }
        return $names;
    }

    public static function clearSchemaCache(): void
    {
        self::$jsonSchemaCache = null;
        self::$attributeNamesCache = null;
    }
}
