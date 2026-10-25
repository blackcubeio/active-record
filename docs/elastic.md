# Elastic — Dynamic JSON Schema Attributes

Dynamic model attributes driven by JSON Schema. Not EAV. No JOIN hell.

Elastic stores JSON in a single column, validates with JSON Schema, and lets you query virtual columns with `JSON_VALUE()`. You manipulate PHP properties. You never see the JSON.

## How It Works

### Storage

| Column | Purpose |
|--------|---------|
| `elasticSchemaId` | FK to `elasticSchemas` table |
| `_extras` | JSON storage for dynamic attributes |

A JSON Schema defines what properties exist, their types, and validation rules. The schema is stored in the `elasticSchemas` table. The model links to it via `elasticSchemaId`.

### Architecture

```
ElasticSchemaCache   — static cache (one query, one parse per schemaId)
BaseElasticTrait     — prefixed methods (tryElasticGet, elasticPopulateProperty, ...)
ElasticTrait         — composite: __get/__set/__isset/__call dispatch + AR overrides
ElasticInterface     — contract for elastic models
BaseElasticQueryTrait — virtual column resolution in WHERE/ORDER BY
ElasticQueryTrait    — composite query trait
```

## Setup

### Model

```php
use Blackcube\ActiveRecord\Elastic\ElasticInterface;
use Blackcube\ActiveRecord\Elastic\ElasticTrait;
use Yiisoft\ActiveRecord\ActiveRecord;

class Article extends ActiveRecord implements ElasticInterface
{
    use ElasticTrait;

    protected int $id;
    protected string $title = '';

    public function tableName(): string
    {
        return '{{%articles}}';
    }

    // Customize column names (optional):
    // public function elasticColumn(): string { return '_data'; }
    // public function elasticSchemaColumn(): string { return 'schemaId'; }
}
```

No `MagicComposeActiveRecordTrait` needed. `ElasticTrait` handles `__get`, `__set`, `__isset`, `__call`, `populateProperty`, `propertyValuesInternal`, `populateRecord`, and `refreshInternal` directly.

### Query class

```php
use Blackcube\ActiveRecord\Elastic\BaseElasticQueryTrait;
use Blackcube\ActiveRecord\Elastic\BuildElasticFormulaeTrait;
use Blackcube\ActiveRecord\Elastic\ElasticQueryInterface;
use Blackcube\ActiveRecord\QualifyColumnTrait;
use Blackcube\ActiveRecord\BuildFormulaeTrait;
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

Or extend the abstract class:

```php
use Blackcube\ActiveRecord\AbstractElasticQuery;

class ArticleQuery extends AbstractElasticQuery
{
    // scopes, caching, etc.
}
```

## Usage

### Working with dynamic attributes

```php
// Elastic attributes work like regular PHP properties
$article->author = 'Philippe';
$article->rating = 5;
$article->save();

// Read back
$loaded = Article::query()->andWhere(['id' => $article->getId()])->one();
echo $loaded->author; // 'Philippe'
echo $loaded->rating; // 5

// A name that is neither a schema property nor a model property
$article->unknown = 1; // Yiisoft\ActiveRecord\UnknownPropertyException: Setting unknown property
```

### Querying virtual columns

```php
// WHERE with virtual columns — automatically converted to JSON_VALUE()
$articles = Article::query()
    ->andWhere(['author' => 'Philippe'])
    ->orderBy(['author' => SORT_ASC])
    ->each();

// Operator format
$articles = Article::query()
    ->andWhere(['>', 'rating', 3])
    ->each();
```

`JSON_VALUE()` returns text. A comparison with a number (`['>', 'rating', 3]`) compares numerically, but `orderBy()` on a virtual column sorts as text: `10` comes before `9`.

### Validation

```php
use Blackcube\ActiveRecord\Elastic\Validator\ElasticRulesManager;
use Yiisoft\Validator\Validator;

$rulesManager = new ElasticRulesManager();
$rules = $rulesManager->getRules($article);
// Returns ['propertyName' => [Rule, Rule, ...], ...]

$validator = new Validator();
$result = $validator->validate($article->getElasticValues(), $rules);
```

### Labels, hints, placeholders

```php
$article->getPropertyLabel('author');      // From JSON Schema 'title' field
$article->getPropertyHint('author');       // From JSON Schema 'description' field
$article->getPropertyPlaceholder('author'); // From JSON Schema 'placeholder' field
```

Priority: model method > elastic schema > generated from property name.

### Protection

The `_extras` column is protected by default. You cannot set it directly:

```php
$article->_extras = '{}'; // Error: Cannot set read-only elastic column
```

Use `protectElastic(false)` to temporarily disable protection (for migrations, etc.).

## Combining with other traits

For models that need both Elastic and Hazeltree, use `HazeltreeElasticTrait`:

```php
use Blackcube\ActiveRecord\HazeltreeElasticTrait;
use Blackcube\ActiveRecord\Elastic\ElasticInterface;
use Blackcube\ActiveRecord\Hazeltree\HazeltreeInterface;

class Content extends ActiveRecord implements ElasticInterface, HazeltreeInterface
{
    use HazeltreeElasticTrait;
}
```

## API Reference

### ElasticInterface

| Method | Description |
|--------|-------------|
| `elasticColumn()` | JSON storage column name (default: `_extras`) |
| `elasticSchemaColumn()` | Schema FK column name (default: `elasticSchemaId`) |
| `getSchema()` | Parsed Swaggest Schema object |
| `getElasticAttributes()` | Names of the schema properties, as written in the schema |
| `getElasticValues()` | Key-value array of current attribute values |
| `getElasticLabels()` | Labels from JSON Schema `title` fields |
| `getElasticHints()` | Hints from JSON Schema `description` fields |
| `getElasticPlaceholders()` | Placeholders from JSON Schema |
| `getPropertyLabel($prop)` | Label with fallback chain |
| `getPropertyHint($prop)` | Hint with fallback chain |
| `getPropertyPlaceholder($prop)` | Placeholder with fallback chain |

### ElasticSchemaCache

| Method | Description |
|--------|-------------|
| `preloadAllSchemas()` | Load all schemas from DB into cache |
| `getCachedJsonSchema($id)` | Get cached JSON schema string |
| `getCachedAttributeNames($id)` | Get cached attribute name map |
| `clearSchemaCache()` | Clear the static schema cache |

### ElasticQueryInterface

| Method | Description |
|--------|-------------|
| `getRealAttributes()` | Real DB column names (for virtual column detection) |
