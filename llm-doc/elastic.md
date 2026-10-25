# active-record — Elastic (dynamic attributes)

## Storage

| Element | Default | Override |
|---|---|---|
| JSON storage column | `_extras` (TEXT/JSON) | `elasticColumn(): string` |
| Schema FK column | `elasticSchemaId` | `elasticSchemaColumn(): string` |
| Schema table | `{{%elasticSchemas}}` (`id`, `name` unique, `schema` TEXT, `view`, `dateCreate`, `dateUpdate`) | — (migration `Elastic\Migrations\M000000000000CreateElasticSchemas`, AR `Elastic\ElasticSchema`) |

## Schema loading

- First need of a schema → `ElasticSchemaCache::preloadAllSchemas()`: every row of `elasticSchemas` in one query, kept in static memory for the process.
- A schema changed in database is not seen until `ElasticSchemaCache::clearSchemaCache()` (or `<Model>::clearSchemaCache()`) or a new process.
- No schema id → default schema `{"type":"object","properties":{},"required":[]}`: no elastic property.
- `getSchema(): ?Schema` (Swaggest); a schema that fails to parse → `null`, logged through `Injector` + `LoggerInterface` when registered.

## Property access (model with a composite trait)

| Operation | Behaviour |
|---|---|
| `$model->sku`, `$model->SKU`, `$model->getSku()` | value from the JSON (names are case-insensitive) |
| `$model->sku = 'A'`, `$model->setSku('A')` | stored in memory, marks the elastic data dirty |
| `$model->unknown = 1` (not in schema, not a model property) | `Yiisoft\ActiveRecord\UnknownPropertyException` `Setting unknown property: <Class>::unknown` |
| `$model->_extras` (storage column) | the serialized JSON |
| `$model->_extras = '{}'` | `Error` `Cannot set read-only elastic column <Class>::$_extras. Use individual properties instead.`; `protectElastic(false)` lifts it |
| `$model->elasticSchemaId = 3` | allowed; schema properties re-read, values kept |

## Serialization on save

| State | Stored JSON |
|---|---|
| no elastic property changed | the JSON as loaded (keys outside the schema kept) |
| a property changed, schema set | only the schema properties (keys outside the schema dropped) |
| a property changed, no schema | every value |

Measured: `{"sku":"A9","price":9,"legacy":1}` stays as is on a save that changes only a real column; after `inStock = true` it becomes `{"sku":"A9","price":9,"inStock":true}`.

## Metadata

| Method | Source |
|---|---|
| `getElasticAttributes()` | schema property names as written (`["sku","price","inStock"]`) |
| `getElasticValues()` | `[name => value]` for every schema property (`null` when missing) |
| `getElasticLabels()` / `getElasticHints()` / `getElasticPlaceholders()` | schema `title` / `description` / `placeholder` |
| `getPropertyLabel($p)` | model `getPropertyLabels()` → schema `title` → generated (`inStock` → `In Stock`) |
| `getPropertyHint($p)`, `getPropertyPlaceholder($p)` | model `getPropertyHints()` / `getPropertyPlaceholders()` → schema → `''` |

## Queries (`BaseElasticQueryTrait`)

- Virtual column = a name that is not a column of the model table (table columns cached statically per model class; `clearRealAttributesCache()`).
- Converted to `JSON_VALUE(<alias or table>.[[<storage>]], '$.<name>')` in `where`/`andWhere`/`orWhere`/`setWhere` (hash keys, column at index 1 of operators `=`, `!=`, `<>`, `>`, `<`, `>=`, `<=`, `IN`, `NOT IN`, `BETWEEN`, `NOT BETWEEN`, `LIKE`, `NOT LIKE`, `OR LIKE`, `OR NOT LIKE`, nested `AND`/`OR`/`NOT`) and in `orderBy`/`addOrderBy` (array form).
- `table.column` with the model table is converted; another table prefix is not.
- Not converted: `select`, `groupBy`, `having`, string conditions, `Expression`.
- `JSON_VALUE()` returns text: `['>', 'price', 50]` compares numerically, `orderBy(['price' => SORT_ASC])` sorts as text (`10`, `100`, `9`).

## Validation

`(new Elastic\Validator\ElasticRulesManager())->getRules($model)` → `[property => list<RuleInterface>]` from the schema (`JsonSchemaRuleMapper`):

| Schema | Rules |
|---|---|
| in `required` | `Required`; otherwise every rule has `skipOnEmpty` |
| `string` | `StringValue`, or `Email` (`email`), `Email(enableIdn)` (`idn-email`), `Url` (`uri`, `url`), `Ip` (`ipv4`, `ipv6`); + `Length` (`minLength`/`maxLength`); + `Regex` (`pattern`) |
| `integer` / `number` | `Integer` / `Number` (`minimum`, `maximum`) |
| `boolean` | `BooleanValue` |
| `enum` | `In` |

Measured: `sku` (required, maxLength 8) → `Required, StringValue, Length`; `price` → `Number`; `inStock` → `BooleanValue`. Validate `getElasticValues()` against these rules.
