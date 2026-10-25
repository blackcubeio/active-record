# Integration

## Scoped queries

### Setting up a query class

```php
use Blackcube\ActiveRecord\QualifyColumnTrait;
use Blackcube\ActiveRecord\ScopableTrait;
use Blackcube\ActiveRecord\ScopableQueryInterface;
use Yiisoft\ActiveRecord\ActiveQuery;

class ProductQuery extends ActiveQuery implements ScopableQueryInterface
{
    use ScopableTrait;
    use QualifyColumnTrait;

    public static function create(string $modelClass): static
    {
        $query = new static($modelClass);
        $query->addScope(ActiveScope::class)
              ->addScope(LanguageScope::class);
        return $query;
    }
}
```

### Usage

```php
$products = Product::query()->active()->each();

$products = Product::query()
    ->active(active: false)
    ->language(languageId: 'fr')
    ->each();

$products = Product::query()
    ->active()
    ->andWhere(['like', 'title', 'laptop'])
    ->each();
```

### Shared state between scopes

```php
$query->atDate(date: '2025-06-01')->available();
```

### Subquery support (expression builders)

`yiisoft/db` only registers `Query::class` in its expression builders. Any `ActiveQuery` subclass will throw `NotSupportedException` when used as a subquery. Register your query classes:

```php
$connection->getQueryBuilder()->setExpressionBuilders([
    ActiveQuery::class => QueryExpressionBuilder::class,
    ProductQuery::class => QueryExpressionBuilder::class,
]);
```

## Elastic models

### Model setup

```php
use Blackcube\ActiveRecord\Elastic\ElasticInterface;
use Blackcube\ActiveRecord\Elastic\ElasticTrait;
use Yiisoft\ActiveRecord\ActiveRecord;

class Article extends ActiveRecord implements ElasticInterface
{
    use ElasticTrait;

    protected int $id;
    protected string $title = '';

    public function tableName(): string { return '{{%articles}}'; }
}
```

`ElasticTrait` handles `__get`, `__set`, `__isset`, `__call`, `populateProperty`, `propertyValuesInternal`, `populateRecord`, and `refreshInternal`. No `MagicComposeActiveRecordTrait` needed.

### Query class

Use inheritance to avoid trait collision between `QualifyColumnTrait` and `BaseElasticQueryTrait` (both define `where()`):

```php
use Blackcube\ActiveRecord\QualifyColumnTrait;
use Blackcube\ActiveRecord\BuildFormulaeTrait;
use Blackcube\ActiveRecord\Elastic\BaseElasticQueryTrait;
use Blackcube\ActiveRecord\Elastic\BuildElasticFormulaeTrait;
use Yiisoft\ActiveRecord\ActiveQuery;

class BaseArticleQuery extends ActiveQuery
{
    use QualifyColumnTrait;
    use BuildFormulaeTrait;
}

class ArticleQuery extends BaseArticleQuery implements ElasticQueryInterface
{
    use BaseElasticQueryTrait;
    use BuildElasticFormulaeTrait;
}
```

Or extend `AbstractElasticQuery` which handles this for you.

## Hazeltree models

### Model setup

```php
use Blackcube\ActiveRecord\Hazeltree\HazeltreeInterface;
use Blackcube\ActiveRecord\Hazeltree\HazeltreeTrait;
use Yiisoft\ActiveRecord\ActiveRecord;

class Menu extends ActiveRecord implements HazeltreeInterface
{
    use HazeltreeTrait;

    protected int $id;
    protected string $name = '';

    public function tableName(): string { return '{{%menus}}'; }

    public static function query($modelClass = null): ActiveQueryInterface
    {
        return new MenuQuery($modelClass ?? static::class);
    }
}
```

### Query class

```php
use Blackcube\ActiveRecord\Hazeltree\BaseHazeltreeQueryTrait;
use Blackcube\ActiveRecord\Hazeltree\HazeltreeQueryInterface;
use Yiisoft\ActiveRecord\ActiveQuery;

class MenuQuery extends ActiveQuery implements HazeltreeQueryInterface
{
    use BaseHazeltreeQueryTrait;
}
```

## Combined Elastic + Hazeltree

For models needing both dynamic attributes and tree structure:

```php
use Blackcube\ActiveRecord\HazeltreeElasticTrait;
use Blackcube\ActiveRecord\Elastic\ElasticInterface;
use Blackcube\ActiveRecord\Hazeltree\HazeltreeInterface;

class Content extends ActiveRecord implements ElasticInterface, HazeltreeInterface
{
    use HazeltreeElasticTrait;
}
```

Query class:

```php
use Blackcube\ActiveRecord\AbstractHazeltreeElasticQuery;

class ContentQuery extends AbstractHazeltreeElasticQuery
{
    // scopes, cache, etc.
}
```

## Simple models (setter-first only)

For models that only need setter-first `populateProperty` without Elastic or Hazeltree:

```php
use Blackcube\ActiveRecord\PopulatePropertyTrait;

class SimpleModel extends ActiveRecord
{
    use PopulatePropertyTrait;
    // setters are called during populateRecord/populateProperty
}
```

## MagicCompose integration

For models using custom composed traits with MagicCompose attributes (`#[MagicGetter]`, etc.):

```php
use Blackcube\MagicCompose\MagicComposeActiveRecordTrait;

class ComposedModel extends ActiveRecord
{
    use MagicComposeActiveRecordTrait;
    // + traits with MagicCompose attributes (#[MagicGetter], etc.)
}
```

`MagicComposeActiveRecordTrait` is provided by `blackcube/magic-compose` (`composer require blackcube/magic-compose`). For models that only need Elastic and/or Hazeltree, the composite traits handle dispatch directly.
