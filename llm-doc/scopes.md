# active-record — Scopes, qualification, formulae, batched each

## Scopes

```php
interface ScopeInterface
{
    public static function name(): string;   // method name on the query
    public function process(ScopableQueryInterface $query, string $modelClass, ?ScopeParametersInterface $parameters = null): void;
}
```

`ScopableTrait` (on an `ActiveQuery`, implements `ScopableQueryInterface`):

| Method | Behaviour |
|---|---|
| `addScope(string $scopeClass): static` | `new $scopeClass()` stored under `$scopeClass::name()`; a second scope with the same name replaces the first |
| `removeScope(string $scopeClass): static`, `hasScope(string $scopeClass): bool` | by `name()` |
| `setState(string $key, mixed $value): static`, `getState(string $key, mixed $default = null): mixed` | state shared by the scopes of one query |
| `__call(string $name, array $params): static` | registered name → `process($this, <model class>, $parameters)`; otherwise `BadMethodCallException` `Unknown scope or method: <QueryClass>::<name>()` |

Parameters: `$parameters` = `new ScopeParameters($params)`, or `null` when called without argument. Only **named** arguments are reachable: `ScopeParameters::value('active')` reads the key `active`.

| Call | `value('active')` | SQL with `andWhere(['active' => $parameters?->value('active') ?? true])` |
|---|---|---|
| `->active(active: false)` | `false` | `active = FALSE` |
| `->active(false)` | `null` (positional key `0`) | `active = TRUE` |
| `->active()` | no parameters | `active = TRUE` |

`ScopeParameters` is immutable; `value()` of a missing key → `null`.

## `QualifyColumnTrait`

Overrides `where()`, `andWhere()`, `orWhere()`; qualifier = the alias (`alias('t')`) or the model `tableName()`; `alias()` resets the cached qualifier.

| Condition | Result |
|---|---|
| hash `['title' => 'x']` | `{{%testModels}}.[[title]] = :qp0` |
| hash with `alias('t')` | `t.[[title]] = :qp0` |
| operator `['like', 'title', 'x']` (column at index 1) | `{{%testModels}}.[[title]] LIKE :qp0` |
| nested `['or', [...], [...]]` / `and` / `not` | each element qualified |
| already qualified `t.title`, or containing `(` or `[[` | untouched |
| string condition, `Expression` | untouched |

`ensureTableName()` / `getQualifier()` are `protected`.

## Formulae

`FormulaeExpressionInterface::buildFormulae(callable $callback, array $columns, array $params): Expression` — the callback receives each qualified column and returns a SQL fragment; fragments are joined with ` + `:

```php
$query->buildFormulae(fn (string $column) => 'COALESCE(' . $column . ', 0)', ['active', 'id'], []);
// COALESCE({{%testModels}}.[[active]], 0) + COALESCE({{%testModels}}.[[id]], 0)
```

`BuildFormulaeTrait` needs `QualifyColumnTrait` on the same class. `BuildElasticFormulaeTrait` turns virtual columns into `JSON_VALUE(...)` before the callback (see `elastic.md`).

## Batched `each()`

`BatchedEachQueryTrait::each(int $batchSize = 100): DataReaderInterface` reads through `batch()`: relations loaded with `with()` cost one query per relation per batch (Yii3's own `each()` runs them per row). It returns a `BatchedDataReader`, iterable and rewindable, one model per step. `count()`, `indexBy()`, `resultCallback()`, `typecastColumns()` throw `LogicException`.

## `PopulatePropertyTrait`

Setter-first `populateProperty()`: `set<Name>()` when it exists, otherwise `parent::populateProperty()`. Only for models without a composite trait (Elastic/Hazeltree composites already do it).

## Abstract classes

| Class | Traits | Implements |
|---|---|---|
| `AbstractScopableQuery` (extends `ActiveQuery`) | `ScopableTrait`, `QualifyColumnTrait`, `BuildFormulaeTrait`, `BatchedEachQueryTrait` | `ScopableQueryInterface`, `FormulaeExpressionInterface` |
| `AbstractElasticQuery` | + `BaseElasticQueryTrait`, `BuildElasticFormulaeTrait` | + `ElasticQueryInterface` |
| `AbstractHazeltreeQuery` | + `BaseHazeltreeQueryTrait` | + `HazeltreeQueryInterface` |
| `AbstractHazeltreeElasticQuery` | + `BaseElasticQueryTrait`, `BaseHazeltreeQueryTrait`, `BuildElasticFormulaeTrait` | + both |
| `AbstractElasticActiveRecord` (extends `ActiveRecord`) | `ElasticTrait` | `ElasticInterface` |
| `AbstractHazeltreeActiveRecord` | `HazeltreeTrait` | `HazeltreeInterface` |
| `AbstractHazeltreeElasticActiveRecord` | `HazeltreeElasticTrait` | `ElasticInterface`, `HazeltreeInterface` |
