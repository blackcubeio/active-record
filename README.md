# Blackcube ActiveRecord

Scoped queries, elastic dynamic attributes, Hazeltree nested sets — a toolkit for ActiveRecord.

[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)
[![Packagist Version](https://img.shields.io/packagist/v/blackcube/active-record.svg)](https://packagist.org/packages/blackcube/active-record)

## Quickstart

```bash
composer require blackcube/active-record
```

```php
// Scoped queries
$products = Product::query()->active()->language(languageId: 'fr')->each();

// Elastic — dynamic attributes from JSON Schema
$article->author = 'Philippe';
$article->rating = 5;
$article->save();
$found = Article::query()->andWhere(['author' => 'Philippe'])->each();

// Hazeltree — nested sets
$child = new Menu();
$child->name = 'About';
$child->saveInto($homepage);
$breadcrumb = $child->relativeQuery()->parent()->includeAncestors()->includeSelf()->each();
```

## Three building blocks

### Scoped queries

Named, composable filters. PHP 8 named arguments flow through `__call` into typed parameters.

```php
class ProductQuery extends ActiveQuery implements ScopableQueryInterface
{
    use ScopableTrait;
    use QualifyColumnTrait;
}

$products = Product::query()
    ->active(active: false)
    ->language(languageId: 'fr')
    ->andWhere(['like', 'title', 'laptop'])
    ->each();
```

`QualifyColumnTrait` auto-prefixes column names with the table qualifier. Scopes write simple names, qualification is transparent. No ambiguous column errors in JOINs.

### Elastic — Dynamic JSON Schema attributes

JSON column + JSON Schema = dynamic attributes without EAV.

```php
class Article extends ActiveRecord implements ElasticInterface
{
    use ElasticTrait;  // handles __get/__set dispatch, no MagicCompose needed
}

// Properties come from JSON Schema, not PHP class definition
$article->author = 'Philippe';     // stored in _extras JSON column
$article->rating = 5;
$article->save();

// Query virtual columns — automatically converted to JSON_VALUE()
// JSON_VALUE() returns text: ORDER BY on a virtual column sorts as text (10 before 9)
$top = Article::query()->andWhere(['>', 'rating', 3])->orderBy(['author' => SORT_ASC])->each();

// Validation from JSON Schema
$rulesManager = new ElasticRulesManager();
$rules = $rulesManager->getRules($article);
```

### Hazeltree — Nested sets with rational numbers

Tree structure in RDBMS. Read branches in one query. Write without global renumbering.

Based on [Dan Hazel's research (2008)](https://arxiv.org/abs/0806.3115).

```php
class Menu extends ActiveRecord implements HazeltreeInterface
{
    use HazeltreeTrait;  // handles __get/__set dispatch, no MagicCompose needed
}

// Write
$home = new Menu(); $home->name = 'Home'; $home->save();           // path: 1
$about = new Menu(); $about->name = 'About'; $about->saveInto($home); // path: 1.1
$blog = new Menu(); $blog->name = 'Blog'; $blog->saveAfter($about);  // path: 1.2

// Read — one query each
$children   = $home->relativeQuery()->children()->each();
$breadcrumb = $about->relativeQuery()->parent()->includeAncestors()->includeSelf()->each();
$siblings   = $about->relativeQuery()->siblings()->next()->each();
$roots      = Menu::query()->roots()->each();
```

### Combined — Elastic + Hazeltree

For models that need both dynamic attributes and tree structure:

```php
class Content extends ActiveRecord implements ElasticInterface, HazeltreeInterface
{
    use HazeltreeElasticTrait;  // dispatches to both, resolves all collisions
}
```

## Trait architecture

Three layers, zero collision:

| Layer | Purpose | Example |
|-------|---------|---------|
| **Base traits** | Prefixed methods, `protected`, no collision | `BaseElasticTrait`, `BaseHazeltreeTrait` |
| **Composite traits** | `__get`/`__set` dispatch, one per model | `ElasticTrait`, `HazeltreeTrait`, `HazeltreeElasticTrait` |
| **Abstract classes** | Convenience, just `use Trait` | `AbstractElasticActiveRecord`, `AbstractHazeltreeElasticQuery` |

Base traits use `tryElasticGet()` / `tryHazeltreeGet()` returning `bool`. Composite traits chain: elastic, then hazeltree, then `parent::` when the parent class has the magic method; otherwise an unknown property throws `Yiisoft\ActiveRecord\UnknownPropertyException`. No `insteadof`, no MagicCompose needed.

## Tests

```bash
vendor/bin/codecept run
```

503 tests, 2882 assertions across 18 suites.

## Documentation

- [Overview & prerequisites](docs/index.md)
- [Installation](docs/installation.md)
- [API — Scopes & Qualification](docs/api.md)
- [API — Elastic](docs/elastic.md)
- [API — Hazeltree](docs/hazeltree.md)
- [Integration](docs/integration.md)

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).

## Author

Philippe Gaultier <philippe@blackcube.io>
