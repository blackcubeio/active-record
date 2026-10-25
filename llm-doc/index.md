# blackcube/active-record — LLM index

**Role:** toolkit for Yii3 ActiveRecord: named query scopes with automatic column qualification, batched `each()`, Elastic (JSON-Schema-driven dynamic attributes stored in one JSON column, queryable as virtual columns), Hazeltree (nested sets keyed by rational numbers: path, left, right, level).

## Package card

| | |
|---|---|
| Namespace | `Blackcube\ActiveRecord` |
| Scopes & queries | `ScopableTrait`, `ScopeInterface`, `ScopeParameters(Interface)`, `ScopableQueryInterface`, `QualifyColumnTrait`, `BuildFormulaeTrait`, `FormulaeExpressionInterface`, `BatchedEachQueryTrait`, `BatchedDataReader`, `PopulatePropertyTrait` |
| Elastic | `Elastic\ElasticInterface`, `Elastic\ElasticTrait` (composite), `Elastic\BaseElasticTrait`, `Elastic\ElasticQueryInterface`, `Elastic\BaseElasticQueryTrait`, `Elastic\ElasticQueryTrait`, `Elastic\BuildElasticFormulaeTrait`, `Elastic\ElasticSchema` (AR), `Elastic\ElasticSchemaCache`, `Elastic\Validator\ElasticRulesManager`, `Elastic\Validator\JsonSchemaRuleMapper`, `Elastic\Migrations\M000000000000CreateElasticSchemas` |
| Hazeltree | `Hazeltree\HazeltreeInterface`, `Hazeltree\HazeltreeTrait` (composite), `Hazeltree\BaseHazeltreeTrait`, `Hazeltree\HazeltreeQueryInterface`, `Hazeltree\BaseHazeltreeQueryTrait`, `Hazeltree\HazeltreeQueryTrait`, `Hazeltree\Helpers\TreeHelper`, `Hazeltree\Helpers\Matrix`, `Hazeltree\Exceptions\InvalidItemConfigurationException`, `Hazeltree\Exceptions\InvalidSegmentException` |
| Combined | `HazeltreeElasticTrait` (composite), `HazeltreeElasticQueryTrait` |
| Abstract classes | `AbstractScopableQuery`, `AbstractElasticQuery`, `AbstractHazeltreeQuery`, `AbstractHazeltreeElasticQuery`, `AbstractElasticActiveRecord`, `AbstractHazeltreeActiveRecord`, `AbstractHazeltreeElasticActiveRecord` |
| Requires | PHP ^8.2, `ext-intl`, `blackcube/injector` ^1.0, `psr/log` ^3.0, `swaggest/json-schema` ^0.12.43, `yiisoft/active-record` ^1.1, `yiisoft/cache` ^3.2, `yiisoft/db` ^2.1, `yiisoft/event-dispatcher` ^1.1, `yiisoft/validator` ^2.6. MySQL/MariaDB (`JSON_VALUE()`). |
| Yii config | No config-plugin. |
| License | BSD-3-Clause |

## Rules

- One composite trait per model: `ElasticTrait` OR `HazeltreeTrait` OR `HazeltreeElasticTrait`. Each owns `__get`/`__set`/`__isset`/`__call`, `populateProperty`, `propertyValuesInternal`, `populateRecord`, `refreshInternal` (+ `deleteInternal` for Hazeltree).
- Composite dispatch: elastic, then hazeltree, then `parent::` if the parent class has the magic method; otherwise unknown get/set → `Yiisoft\ActiveRecord\UnknownPropertyException`, unknown call → `Error: Call to undefined method …`.
- `QualifyColumnTrait` and `BaseElasticQueryTrait` both redefine `where`/`andWhere`/`orWhere`: never in the same class; qualification in the parent class, Elastic in the child (as `AbstractElasticQuery extends AbstractScopableQuery`).
- Hazeltree columns (`path`, `left`, `right`, `level`) and the Elastic storage column are read-only from outside: write the tree through `saveInto()`/`saveBefore()`/`saveAfter()`, the JSON through the elastic properties.
- Tree order = `natural()` / `reverse()` on the query, never a manual `orderBy` on `left`.
- Elastic properties are read and written as model properties; queries use their names as columns (`andWhere(['color' => 'red'])`), converted to `JSON_VALUE()`. Never write `JSON_VALUE`/`JSON_EXTRACT` or the storage column by hand.
- `each()` of the scopable queries is batched (`BatchedEachQueryTrait`): `with()` costs one query per relation per batch; the reader is iterable only (`count()`, `indexBy()`, `resultCallback()`, `typecastColumns()` throw `LogicException`).

## Parts

| File | Content | Read when |
|---|---|---|
| `scopes.md` | Scopes (`addScope`, `__call` dispatch, named arguments, shared state), `QualifyColumnTrait` (what is qualified), formulae, batched `each()`, `PopulatePropertyTrait`, abstract query/AR classes. | before writing a query class or a scope |
| `elastic.md` | Model side (columns, schema loading and cache, property access, protection, serialization, labels), query side (virtual column detection, `JSON_VALUE`, text ordering), validation rules from the schema, measured behaviours. | before using dynamic attributes |
| `hazeltree.md` | Model side (columns, protection, `save()` as root, `saveInto`/`saveBefore`/`saveAfter`, `canMove`, `delete`, level helpers), query side (scopes, modifiers, ordering, alias), `TreeHelper`, measured behaviours. | before manipulating a tree |
| `integration.md` | Tables and migrations, model/query wiring per feature, `ConnectionProvider`, `Injector` (logger), subquery expression builders. | before plugging the package in |
| `extension.md` | Column names, custom scopes, schema metadata, overridable hooks; what does not extend. | before extending |
