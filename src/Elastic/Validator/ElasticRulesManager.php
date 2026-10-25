<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Elastic\Validator;

use Swaggest\JsonSchema\Schema;
use Yiisoft\Validator\RuleInterface;

/**
 * Builds Yii3 validation rules from a model using ElasticTrait.
 *
 * Extracts the JSON Schema from the model and uses JsonSchemaRuleMapper
 * to generate the validation rules of each property.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ElasticRulesManager
{
    /**
     * Validation rules of a model, built from its JSON Schema.
     *
     * @param object $model Model with getSchema() and getExtras() methods
     * @return array<string, list<RuleInterface>>
     */
    public function getRules(object $model): array
    {
        $rules = [];
        $schema = null;
        $properties = null;

        if (method_exists($model, 'getSchema') === true) {
            $schema = $model->getSchema();
        }
        if ($schema instanceof Schema === true) {
            $properties = $schema->getProperties();
        }

        if ($properties !== null) {
            $required = $schema->required ?? [];
            $mapper = new JsonSchemaRuleMapper();

            foreach ($properties as $name => $property) {
                $propertyRules = $mapper->map($name, $property, $required);
                if ($propertyRules !== []) {
                    $rules[$name] = $propertyRules;
                }
            }
        }

        return $rules;
    }
}
