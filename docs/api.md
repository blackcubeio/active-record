# API — Scopes & Qualification

Core scope system, column qualification, and formulae. For Elastic and Hazeltree APIs, see [elastic.md](elastic.md) and [hazeltree.md](hazeltree.md).

## Interfaces

### ScopeInterface

Contract for named query scopes. Each scope has a name (used as method name via `__call`) and a `process()` method.

```php
interface ScopeInterface
{
    public static function name(): string;
    public function process(
        ScopableQueryInterface $query,
        string $modelClass,
        ?ScopeParametersInterface $parameters = null,
    ): void;
}
```

**Example — an ActiveScope:**

```php
use Blackcube\ActiveRecord\ScopeInterface;
use Blackcube\ActiveRecord\ScopableQueryInterface;
use Blackcube\ActiveRecord\ScopeParametersInterface;

class ActiveScope implements ScopeInterface
{
    public static function name(): string
    {
        return 'active';
    }

    public function process(
        ScopableQueryInterface $query,
        string $modelClass,
        ?ScopeParametersInterface $parameters = null,
    ): void {
        $query->andWhere(['active' => $parameters?->value('active') ?? true]);
    }
}
```

### ScopableQueryInterface

Contract for queries supporting dynamic scope registration and shared state.

| Method | Description |
|--------|-------------|
| `addScope(string $scopeClass): static` | Register a scope by class name |
| `removeScope(string $scopeClass): static` | Unregister a scope |
| `hasScope(string $scopeClass): bool` | Check if a scope is registered |
| `setState(string $key, mixed $value): static` | Store shared state |
| `getState(string $key, mixed $default = null): mixed` | Read shared state |

### ScopeParametersInterface

Typed parameter bag carrying named arguments from scope calls.

| Method | Description |
|--------|-------------|
| `value(string $propertyName): mixed` | Get parameter value, `null` if missing |

### FormulaeExpressionInterface

Marker interface for queries that support `buildFormulae()`.

## Classes

### ScopeParameters

Immutable implementation of `ScopeParametersInterface`, backed by a simple associative array.

```php
$params = new ScopeParameters(['active' => false]);
$params->value('active'); // false
$params->value('missing'); // null
```

### Abstract query classes

| Class | Traits included | Implements |
|-------|----------------|------------|
| `AbstractScopableQuery` | `ScopableTrait`, `QualifyColumnTrait`, `BuildFormulaeTrait`, `BatchedEachQueryTrait` | `ScopableQueryInterface`, `FormulaeExpressionInterface` |
| `AbstractElasticQuery` | + `BaseElasticQueryTrait`, `BuildElasticFormulaeTrait` | + `ElasticQueryInterface` |
| `AbstractHazeltreeQuery` | + `BaseHazeltreeQueryTrait` | + `HazeltreeQueryInterface` |
| `AbstractHazeltreeElasticQuery` | + both | + both |

### Abstract AR classes

| Class | Trait included | Implements |
|-------|---------------|------------|
| `AbstractElasticActiveRecord` | `ElasticTrait` | `ElasticInterface` |
| `AbstractHazeltreeActiveRecord` | `HazeltreeTrait` | `HazeltreeInterface` |
| `AbstractHazeltreeElasticActiveRecord` | `HazeltreeElasticTrait` | `ElasticInterface`, `HazeltreeInterface` |

## Traits

### ScopableTrait

Implements `ScopableQueryInterface`. Dispatches method calls to registered scopes via `__call()`.

PHP 8 named arguments are preserved:

```php
$query->active(active: false);
// __call('active', ['active' => false])
// -> new ScopeParameters(['active' => false])
// -> scope->process($query, $modelClass, $parameters)
```

Throws `BadMethodCallException` if no scope matches the method name.

### QualifyColumnTrait

Auto-prefixes unqualified column names in `where()`, `andWhere()`, and `orWhere()` conditions with the table or alias qualifier.

| Method | Behavior |
|--------|----------|
| `where($condition, $params)` | Qualifies simple columns before delegating |
| `andWhere($condition, $params)` | Same |
| `orWhere($condition, $params)` | Same |
| `alias(string $alias)` | Resets cached qualifier |

Handles hash conditions (`['col' => val]`), operator conditions (`['>', 'col', val]`), and nested `and`/`or`. Does not touch raw SQL strings or `Expression` objects.

### BuildFormulaeTrait

Builds SQL formulae expressions (e.g., for computed columns). Used by `FormulaeExpressionInterface` queries.

### PopulatePropertyTrait

Standalone setter-first `populateProperty` override. For models that only need setter dispatch without Elastic or Hazeltree. Models using `ElasticTrait`, `HazeltreeTrait`, or `HazeltreeElasticTrait` already have setter-first built in.

### BatchedEachQueryTrait

Overrides `each(int $batchSize = 100)`: the query is read through `batch()`, so relations loaded with `with()` cost one query per batch instead of one per row. Models are returned one by one by a `BatchedDataReader`.

The reader is iterable only: `count()`, `indexBy()`, `resultCallback()` and `typecastColumns()` throw `LogicException`.

### MagicCompose

`MagicComposeActiveRecordTrait` is not part of this package: use `Blackcube\MagicCompose\MagicComposeActiveRecordTrait` from `blackcube/magic-compose`. For models that only need Elastic and/or Hazeltree, the composite traits handle dispatch directly.
