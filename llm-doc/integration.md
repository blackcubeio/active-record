# active-record — Integration

## Split

- Package: traits, abstract classes, `ElasticSchema` AR and its migration, `TreeHelper`. No config-plugin, no DI definition.
- Application: tables, model and query classes, `ConnectionProvider`, `Injector` (optional logger), expression builders for subqueries.

## Connection

Yii3 ActiveRecord reads the connection from `Yiisoft\Db\Connection\ConnectionProvider`. Reference: starter `config/common/bootstrap.php`:

```php
static function (ContainerInterface $container): void {
    ConnectionProvider::set($container->get(ConnectionInterface::class));
},
```

## Tables

| Feature | Columns |
|---|---|
| Elastic | storage column (default `_extras`, TEXT/JSON) + schema FK (default `elasticSchemaId`, BIGINT NULL → `elasticSchemas.id`) |
| Elastic schemas | `{{%elasticSchemas}}`: migration `Blackcube\ActiveRecord\Elastic\Migrations\M000000000000CreateElasticSchemas` (`id`, `name` unique, `schema`, `view`, `dateCreate`, `dateUpdate`) or an application migration with more columns (dcore adds `kind`, `active`, `mdMapping`, …): `ElasticSchema` only reads `id`, `name`, `schema`, `view`, `dateCreate`, `dateUpdate` |
| Hazeltree | `path` VARCHAR(255) unique, `left` / `right` DECIMAL(25,22) (or DOUBLE), `level` INT; indexes on `left`, `right`, `level` |

`ElasticSchemaCache` loads every row of `elasticSchemas`, whatever the extra columns.

## Model and query per feature

| Model | Composite trait | Query class |
|---|---|---|
| Elastic | `ElasticTrait` (or extend `AbstractElasticActiveRecord`) | extends `AbstractElasticQuery`, or a parent with `QualifyColumnTrait` + `BuildFormulaeTrait` and a child with `BaseElasticQueryTrait` + `BuildElasticFormulaeTrait` |
| Hazeltree | `HazeltreeTrait` (or `AbstractHazeltreeActiveRecord`) | extends `AbstractHazeltreeQuery`, or any `ActiveQuery` with `BaseHazeltreeQueryTrait` |
| both | `HazeltreeElasticTrait` (or `AbstractHazeltreeElasticActiveRecord`) | extends `AbstractHazeltreeElasticQuery` |
| scopes only | `PopulatePropertyTrait` (optional) | extends `AbstractScopableQuery` |

The model returns its query class from `query()`:

```php
public static function query(ActiveRecordInterface|string|null $modelClass = null): ActiveQueryInterface
{
    return new MenuQuery($modelClass ?? static::class);
}
```

Hazeltree navigation (`relativeQuery()`) calls `forNode()` only when the query class has it: a model with `HazeltreeTrait` needs a query with `BaseHazeltreeQueryTrait`.

Scopes are registered on the query instance (`addScope()`), typically in the query factory:

```php
$query = new static($modelClass);
$query->addScope(ActiveScope::class)
    ->addScope(LanguageScope::class);
```

## Logger

`BaseElasticTrait::getSchema()` logs a schema that fails to parse through `Blackcube\Injector\Injector::get(LoggerInterface::class)` when `Injector::has(LoggerInterface::class)`; the application initializes `Injector` (`Injector::init($container)`), otherwise nothing is logged.

## Subqueries

`yiisoft/db` only knows `Query::class` as an expression: an `ActiveQuery` subclass used as a subquery throws `NotSupportedException` until it is registered on the connection. Reference: dcore `Helpers/QueryExpressions.php`:

```php
$connection->getQueryBuilder()->setExpressionBuilders([
    ActiveQuery::class => QueryExpressionBuilder::class,
    ProductQuery::class => QueryExpressionBuilder::class,
]);
```

## MagicCompose

`MagicComposeActiveRecordTrait` is not in this package: `Blackcube\MagicCompose\MagicComposeActiveRecordTrait` (`blackcube/magic-compose`).
