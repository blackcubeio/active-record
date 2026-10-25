# active-record — Extension

## Extension points

| Point | Where | Use |
|---|---|---|
| New scope | class implementing `ScopeInterface` (`name()`, `process()`), registered with `addScope()` | a named filter; read named arguments with `$parameters?->value('x')`; share data between scopes with `setState()`/`getState()` |
| Elastic column names | `elasticColumn()`, `elasticSchemaColumn()` (public, on the model) | storage / FK columns other than `_extras` / `elasticSchemaId` |
| Default schema | `public string $defaultJsonSchema` | schema used when the model has no schema id |
| Tree column names | `leftColumn()`, `rightColumn()`, `pathColumn()`, `levelColumn()` (public, on the model) | other column names |
| Labels, hints, placeholders | model methods `getPropertyLabels()`, `getPropertyHints()`, `getPropertyPlaceholders()` | take precedence over the schema metadata |
| Schema metadata | `getSchemaMetadata()` (protected) | change how `title`/`description`/`placeholder` are read |
| Qualification | `getQualifier()`, `ensureTableName()` (protected, `QualifyColumnTrait`) | another qualifier |
| Composite dispatch | write your own composite trait from `BaseElasticTrait` / `BaseHazeltreeTrait` (protected `try*` and `elastic*`/`hazeltree*` hooks) | add a third behaviour; `HazeltreeElasticTrait` is the model to copy |
| Magic chain | `Blackcube\MagicCompose\MagicComposeActiveRecordTrait` (`blackcube/magic-compose`) | models composing their own magic traits |
| Display view of a schema | `ElasticSchema::getDisplayView(?string $basePath, bool $asPath = false)` | view file `<basePath>/<view or snake_case(name)>.php` |
| Validation rules | `JsonSchemaRuleMapper::map()` (public) | reuse or wrap the schema → rules mapping |

## Not extensible

| Element | Reason |
|---|---|
| Virtual column SQL (`JSON_VALUE`, no `RETURNING`) | `private` `buildVirtualColumn()` in `BaseElasticQueryTrait` |
| Operators converted for virtual columns | `private const COLUMN_OPERATORS` / `LOGICAL_OPERATORS` |
| Tree condition building, ordering rules | `private` methods of `BaseHazeltreeQueryTrait` |
| Move algorithm (matrices, sibling shifts) | `private` methods of `BaseHazeltreeTrait` |
| Schema cache | static `ElasticSchemaCache`: one per process, cleared only by `clearSchemaCache()` |
| `BatchedDataReader` options | `count()`, `indexBy()`, `resultCallback()`, `typecastColumns()` throw `LogicException` |
| More than one composite trait per model | the composites each define `__get`/`__set`/…: combine through `HazeltreeElasticTrait` or a dedicated composite |
